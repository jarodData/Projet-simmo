from fastapi import APIRouter, Depends, HTTPException, Header
from sqlalchemy.orm import Session
from database import get_db, fetch_annonces, fetch_historique_user
from schemas.ia_schemas import (
    RequeteRecommandation, ReponseRecommandation,
    RequetePrixPredit, ReponsePrixPredit, RequeteContextuelle
)
from utils.hybrid_engine import MoteurHybride
from config import settings
from models.scoring_contextuel import ScoringContextuel

router  = APIRouter()
moteur  = None  # Initialisé au démarrage
scoreur_contextuel = ScoringContextuel()


def get_moteur():
    return moteur

def verifier_api_key(x_api_key: str = Header(...)):
    if x_api_key != settings.API_KEY:
        raise HTTPException(status_code=401, detail="Clé API invalide.")

# RECOMMANDATIONS PERSONNALISÉES
@router.post("/recommander")
def recommander(
    requete : RequeteRecommandation,
    db      : Session = Depends(get_db),
    _       : None    = Depends(verifier_api_key)
):
    annonces = fetch_annonces(db)

    if not annonces:
        return ReponseRecommandation(total=0, recommandations=[])

    resultats = moteur.recommander(annonces, requete.dict())
    
    
    return ReponseRecommandation(
        total           = len(resultats),
        recommandations = resultats
    )

#  PRÉDICTION DE PRIX
@router.post("/predire-prix", response_model=ReponsePrixPredit)
def predire_prix(
    requete : RequetePrixPredit,
    _       : None = Depends(verifier_api_key)
):
    prediction = moteur.prix.predire_prix(
        ville            = requete.ville,
        categorie        = requete.categorie,
        surface_m2       = requete.surface_m2,
        nb_chambres      = requete.nb_chambres,
        meuble           = requete.meuble,
        type_transaction = requete.type_transaction,
    )

    if prediction['prix_estime'] is None:
        raise HTTPException(
            status_code=503,
            detail="Modèle de prédiction pas encore disponible."
        )

    return ReponsePrixPredit(**prediction)

#  RÉENTRAÎNER LES MODÈLES
@router.post("/entrainer")
def entrainer(
    db : Session = Depends(get_db),
    _  : None    = Depends(verifier_api_key)
):
    annonces = fetch_annonces(db)
    moteur.entrainer(annonces)
    return {"message": f"Modèles entraînés sur {len(annonces)} annonces."}

#  SANTÉ DE L'API
@router.get("/health")
def health():
    return {
        "statut"         : "ok",
        "version"        : settings.VERSION,
        "modele_entraine": moteur.prix.est_entraine if moteur else False
    }
    
    
    #scoreur_contextuel = ScoringContextuel()

#  RECHERCHE CONTEXTUELLE (lieu de travail + religion + loisirs)
@router.post("/recherche-contextuelle")
def recherche_contextuelle(
    requete : RequeteContextuelle,
    db      : Session = Depends(get_db),
    _       : None    = Depends(verifier_api_key)
):
    annonces    = fetch_annonces(db)
    # Juste après : annonces = fetch_annonces(db)
    print("PHOTO ANNONCE 0:", annonces[0].get('photo') if annonces else "vide")
    lieu_geocode = None
    
    lieux_culte  = []

    # ── 1. Géocoder le lieu de référence ────
    if requete.lieu_reference:
        lieu_geocode = scoreur_contextuel.geocoder_lieu(
            requete.lieu_reference,
            requete.ville_reference or ''
        )

    # ── 2. Chercher les lieux de culte ──────
    if requete.religion and lieu_geocode:
        lieux_culte = scoreur_contextuel.chercher_lieux_proches(
            lat      = lieu_geocode['latitude'],
            lon      = lieu_geocode['longitude'],
            type_lieu= 'place_of_worship',
            religion = requete.religion,
            rayon_km = requete.distance_culte_max or 3
        )
    elif requete.religion:
        # Sans lieu de référence, chercher dans toutes les villes
        pass

    # ── 3. Chercher les commodités ───────────
    commodites_trouvees = {}
    if requete.commodites and lieu_geocode:
        for commodite in requete.commodites:
            lieux = scoreur_contextuel.chercher_lieux_proches(
                lat      = lieu_geocode['latitude'],
                lon      = lieu_geocode['longitude'],
                type_lieu= commodite,
                rayon_km = 5
            )
            if lieux:
                commodites_trouvees[commodite] = lieux[:3]

    # ── 4. Filtrer les annonces ──────────────
    filtrees = moteur._appliquer_filtres(annonces, requete.dict())

    if not filtrees:
        return {
            'total'              : 0,
            'lieu_geocode'       : lieu_geocode,
            'lieux_culte_trouves': lieux_culte,
            'annonces'           : []
        }

    # ── 5. Scorer par proximité ──────────────
    if lieu_geocode:
        scores_proximite = scoreur_contextuel.scorer_annonces_par_contexte(
            filtrees,
            lieu_geocode['latitude'],
            lieu_geocode['longitude']
        )
    else:
        scores_proximite = [0.5] * len(filtrees)

    # ── 6. Scores NLP + Prix + Popularité ───
    texte = f"{requete.type_bien or ''} {requete.religion or ''}"
    scores_nlp  = moteur.nlp.calculer_similarite(texte, filtrees)
    scores_prix = moteur._calculer_score_prix(filtrees, requete.dict())
    scores_pop  = moteur._calculer_score_popularite(filtrees)

    # ── 7. Score final hybride ───────────────
    # Proximité 40% | NLP 25% | Prix 25% | Popularité 10%
    resultats = []
    for i, annonce in enumerate(filtrees):

        score_final = (
            scores_proximite[i] * 0.40 +
            scores_nlp[i]       * 0.25 +
            scores_prix[i]      * 0.25 +
            scores_pop[i]       * 0.10
        )

        # Distance au lieu de référence
        distance_ref = None
        if lieu_geocode and annonce.get('latitude') and annonce.get('longitude'):
            distance_ref = round(scoreur_contextuel._haversine(
                lieu_geocode['latitude'], lieu_geocode['longitude'],
                float(annonce['latitude']), float(annonce['longitude'])
            ), 2)

        # Analyse du quartier de l'annonce
        analyse_quartier = {}
        if annonce.get('latitude') and annonce.get('longitude'):
            analyse_quartier = scoreur_contextuel.analyser_contexte_quartier(
                float(annonce['latitude']),
                float(annonce['longitude'])
            )

        # Lieux de culte proches de l'annonce
        culte_annonce = []
        if requete.religion and annonce.get('latitude') and annonce.get('longitude'):
            culte_annonce = scoreur_contextuel.chercher_lieux_proches(
                lat      = float(annonce['latitude']),
                lon      = float(annonce['longitude']),
                type_lieu= 'place_of_worship',
                religion = requete.religion,
                rayon_km = 2
            )[:3]

        # Prédiction prix
        prediction = moteur.prix.predire_prix(
            ville            = annonce.get('ville', ''),
            categorie        = annonce.get('categorie', ''),
            surface_m2       = annonce.get('surface_m2') or 50,
            nb_chambres      = annonce.get('nb_chambres') or 1,
            meuble           = annonce.get('meuble') or False,
            type_transaction = annonce.get('type_transaction', 'location'),
        )

        # Générer la raison contextuelle
        raison = _generer_raison_contextuelle(
            score_final, scores_proximite[i], distance_ref,
            requete, culte_annonce, analyse_quartier
        )

        resultats.append({
            'id_annonce'         : annonce['id'],
            'titre'              : annonce['titre'],
            'prix'               : float(annonce['prix']),
            'categorie'          : annonce['categorie'],
            'ville'              : annonce['ville'],
            'quartier'           : annonce.get('quartier'),
            'latitude'           : annonce.get('latitude'),
            'longitude'          : annonce.get('longitude'),
            'photo'              : annonce.get('photo'),
            'score_final'        : round(float(score_final), 4),
            'score_proximite'    : round(float(scores_proximite[i]), 4),
            'score_nlp'          : round(float(scores_nlp[i]), 4),
            'score_prix'         : round(float(scores_prix[i]), 4),
            'distance_lieu_ref'  : distance_ref,
            'lieux_culte_proches': culte_annonce,
            'analyse_quartier'   : analyse_quartier,
            'commodites_proches' : commodites_trouvees,
            'prix_estime'        : prediction.get('prix_estime'),
            'raison'             : raison,
        })

    # Trier par score final
    resultats.sort(key=lambda x: x['score_final'], reverse=True)
    limite = requete.limite or 10

    return {
        'total'              : len(resultats),
        'lieu_geocode'       : lieu_geocode,
        'lieux_culte_trouves': lieux_culte,
        'commodites_trouvees': commodites_trouvees,
        'annonces'           : resultats[:limite]
    }

# ── Analyser un quartier ─────────────────────
@router.get("/analyser-quartier")
def analyser_quartier(
    lat : float,
    lon : float,
    _   : None = Depends(verifier_api_key)
):
    analyse = scoreur_contextuel.analyser_contexte_quartier(lat, lon)
    return analyse

# ── Générer la raison contextuelle ───────────
def _generer_raison_contextuelle(score, score_prox, distance,
                                  requete, culte, quartier) -> str:
    raisons = []

    if distance is not None:
        if distance <= 1:
            raisons.append(f"à seulement {distance} km de {requete.lieu_reference or 'votre lieu de référence'}")
        elif distance <= 3:
            raisons.append(f"à {distance} km de {requete.lieu_reference or 'votre lieu de référence'}")

    if requete.religion and culte:
        raisons.append(
            f"{len(culte)} lieu(x) de culte {requete.religion} à moins de 2 km"
        )

    if quartier.get('ecoles', 0) > 0:
        raisons.append(f"{quartier['ecoles']} école(s) à proximité")

    if quartier.get('hopitaux', 0) > 0:
        raisons.append("hôpital proche")

    if quartier.get('marches', 0) > 0:
        raisons.append("marché à proximité")

    if not raisons:
        raisons.append("correspond à vos critères")

    return "Ce bien " + ", ".join(raisons) + "."
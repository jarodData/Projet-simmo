# routes/ia_router.py  — version complète fusionnée

from fastapi                          import APIRouter, Query, Depends, HTTPException, Header, BackgroundTasks
from sqlalchemy.orm                   import Session
from apscheduler.schedulers.asyncio   import AsyncIOScheduler
from database                         import get_db, fetch_annonces
from schemas.ia_schemas               import (
    RequeteRecommandation, ReponseRecommandation,
    RequetePrixPredit, ReponsePrixPredit, RequeteContextuelle
)
from utils.hybrid_engine              import MoteurHybride
from models.scoring_contextuel        import ScoringContextuel
from config                           import settings
import logging

import joblib
import re
from schemas.ia_schemas import RequeteNLP




logger = logging.getLogger(__name__)
router = APIRouter(prefix="/api")
moteur= MoteurHybride()
#router = APIRouter()
model = joblib.load('C:\www\projet-simmo\simmo_ia\simmo_ia\modeles\modele_prix.pkl')
# molele_nlp = joblib.load('C:\www\projet-simmo\simmo_ia\models\nlp_model.joblib')
if not moteur.prix.charger_modele():
    print("modele prix non trouve. Lance POST /api/ia/entrainer une fois")
scoreur_contextuel = ScoringContextuel()
scheduler= AsyncIOScheduler()


# ─────────────────────────────────────────────────────
# Utilitaires internes
# ─────────────────────────────────────────────────────

def verifier_api_key(x_api_key: str = Header(..., alias="X-API-Key")):
    """
    Le frontend envoie : 'X-API-Key: simmo-secret-key-2026'
    FastAPI convertit les tirets en underscores si pas d'alias.
    L'alias="X-API-Key" force la lecture du bon header.
    """
    if x_api_key != settings.API_KEY:
        raise HTTPException(status_code=401, detail="Clé API invalide.")


async def recharger_moteur(db_session=None):
    """Charge toutes les annonces actives et ré-entraîne le moteur."""
    try:
        from database import SessionLocal
        db = db_session or SessionLocal()
        annonces = fetch_annonces(db)
        if not db_session:
            db.close()

        if not annonces:
            logger.warning("Aucune annonce active pour entraîner le moteur.")
            return 0

        # 1. Stocker les annonces dans le moteur AVANT entraînement
        moteur.annonces = annonces
        moteur.prix.annonces = annonces  # <-- CRUCIAL pour le print + prédictions

        # # 2. Entraîner les 2 modèles séparément
        # moteur.prix.entrainer(annonces)
        # moteur.nlp.entrainer_sur_corpus(annonces)
        
        # # 3. Sauvegarder les modèles
        # moteur.prix.sauvegarder_modele()
        # moteur.nlp.sauvegarder()


 # NLP : charge si existe, sinon entraîne + sauvegarde
        if not moteur.nlp.charger():
            moteur.nlp.entrainer_sur_corpus(annonces)
            moteur.nlp.sauvegarder()
    
    # Prix : même logique
        if not moteur.prix.charger_modele():
         moteur.prix.entrainer(annonces)
         moteur.prix.sauvegarder_modele()
    
        print(f"Moteur prêt - {len(annonces)} annonces chargées.")

        logger.info(f"Moteur ré-entraîné — {len(annonces)} annonces.")
        return len(annonces)

    except Exception as e:
        logger.error(f"Erreur rechargement moteur : {e}", exc_info=True)
        return 0


# ─────────────────────────────────────────────────────
# Démarrage & arrêt
# ─────────────────────────────────────────────────────

# @router.on_event("startup")
# async def startup():
#     await recharger_moteur()
#     scheduler.add_job(
#         recharger_moteur,
#         trigger          = "interval",
#         hours            = 6,
#         id               = "recharger_moteur",
#         max_instances    = 1,
#         replace_existing = True,
#     )
#     scheduler.start()
#     logger.info("Scheduler démarré — rechargement automatique toutes les 6h.")
def demarrer_scheduler():
    if not scheduler.running:
        scheduler.add_job(
            recharger_moteur,
            trigger          = 'interval',
            hours            = 6,
            id               = 'recharger_moteur',
            max_instances    = 1,
            replace_existing = True,
        )
        scheduler.start()
        print(" Scheduler démarré — rechargement toutes les 6h.")

@router.on_event("shutdown")
async def shutdown():
    scheduler.shutdown(wait=False)
    logger.info("Scheduler arrêté.")


# ─────────────────────────────────────────────────────
# /api/annonces  — page d'accueil
# ─────────────────────────────────────────────────────

@router.get("/annonces")
def get_annonces(
    limit : int     = Query(default=6),
    db    : Session = Depends(get_db),
):
    annonces = fetch_annonces(db)
    if not annonces:
        return {"annonces": [], "total": 0}

    # Tri par id décroissant (les plus récentes)
    annonces_triees = sorted(annonces, key=lambda x: x.get("id", 0), reverse=True)

    return {
        "annonces": annonces_triees[:limit],
        "total":    len(annonces),
    }


# ─────────────────────────────────────────────────────
# /api/recherche  — recherche IA paginée
# ─────────────────────────────────────────────────────

@router.get("/recherche")
def recherche(
    tri:              str   = Query(default="pertinent"),
    page:             int   = Query(default=1),
    q:                str   = Query(default=None),
    ville:            str   = Query(default=None),
    budget_max:       float = Query(default=None),
    budget_min:       float = Query(default=None),
    type_bien:        str   = Query(default=None),
    type_transaction: str   = Query(default=None),
    nb_chambres:      int   = Query(default=None),
    surface_min:      float = Query(default=None),
    meuble:           bool  = Query(default=None),
    latitude:         float = Query(default=None),
    longitude:        float = Query(default=None),
    db:               Session = Depends(get_db),
):
    limite = 12
    page   = max(1, page)

    toutes = fetch_annonces(db)
    if not toutes:
        return {"annonces": [], "total": 0, "page": page, "pages": 0}

    requete = {
        k: v for k, v in {
            "q":               q,
            "ville":           ville,
            "budget_max":      budget_max,
            "budget_min":      budget_min,
            "type_bien":       type_bien,
            "type_transaction":type_transaction,
            "nb_chambres":     nb_chambres,
            "surface_min":     surface_min,
            "meuble":          meuble,
            "latitude":        latitude,
            "longitude":       longitude,
            "limite":          10000,
        }.items() if v is not None
    }

    resultats = moteur.recommander(toutes, requete)

    # Tri secondaire si demandé
    if   tri == "prix_asc":   resultats.sort(key=lambda x: x["prix"])
    elif tri == "prix_desc":  resultats.sort(key=lambda x: x["prix"], reverse=True)
    elif tri == "populaire":  resultats.sort(key=lambda x: x["score_popularite"], reverse=True)
    elif tri == "recent":     resultats.sort(key=lambda x: x["id_annonce"], reverse=True)
    # tri == "pertinent"  →  score_final IA déjà appliqué

    total = len(resultats)
    debut = (page - 1) * limite
    pages = (total + limite - 1) // limite

    return {
        "annonces": resultats[debut : debut + limite],
        "total":    total,
        "page":     page,
        "pages":    pages,
    }


# ─────────────────────────────────────────────────────
# /api/ia/recommander  — recommandations personnalisées
# ─────────────────────────────────────────────────────

@router.post("/ia/recommander")
def recommander(
    requete : RequeteRecommandation,
    db      : Session = Depends(get_db),
    _       : None    = Depends(verifier_api_key),
):
    annonces = fetch_annonces(db)
    if not annonces:
        return ReponseRecommandation(total=0, recommandations=[])
    # filtre par pri avant le moteur NLP
    if requete.budget_max:
        annonces= [a for a in annonces if a.get('prix', 999) <= requete.budget_max]
    if requete.budget_min:
        annonces = [a for a in annonces if a.get('prix', 0)>= requete.budget_min]
    if not annonces:
        return ReponseRecommandation(total= 0, recommandations=[] ,message="Aucune annonce dans le ce buget")

    # Indexer les photos par id AVANT d'appeler le moteur
    photos = {a["id"]: a.get("photo") for a in annonces}

    resultats = moteur.recommander(annonces, requete.dict())

    # Réinjecter la photo dans chaque résultat
    for r in resultats:
        id_annonce = r.get("id_annonce") or r.get("id")
        r["photo"] = photos.get(id_annonce)  
        #  Ajoute ce print pour vérifier
        print("RESULTAT PHOTO:", resultats[0].get("photo") if resultats else "vide")


    return ReponseRecommandation(
        total           = len(resultats),
        recommandations = resultats,
    )
# ─────────────────────────────────────────────────────
# /api/ia/predire-prix
# ─────────────────────────────────────────────────────

@router.post("/ia/predire-prix", response_model=ReponsePrixPredit)
def predire_prix(
    requete : RequetePrixPredit,
    _       : None = Depends(verifier_api_key),
):
    prediction = moteur.prix.predire_prix(
        ville            = requete.ville,
        categorie        = requete.categorie,
        surface_m2       = requete.surface_m2,
        nb_chambres      = requete.nb_chambres,
        meuble           = requete.meuble,
        type_transaction = requete.type_transaction,
        quartier= requete.quartier or "Inconnu" ,
    )
    if prediction["prix_estime"] is None:
        raise HTTPException(
            status_code=503,
            detail="Modèle de prédiction pas encore disponible."
        )
    return ReponsePrixPredit(**prediction)


# ─────────────────────────────────────────────────────
# /api/ia/entrainer  — entraînement manuel
# ─────────────────────────────────────────────────────

@router.post("/ia/entrainer")
def entrainer(
    db : Session = Depends(get_db),
    _  : None    = Depends(verifier_api_key),
):
    annonces = fetch_annonces(db)
    moteur.entrainer(annonces)
    return {"message": f"Modèles entraînés sur {len(annonces)} annonces."}


# ─────────────────────────────────────────────────────
# /api/ia/recharger  — rechargement en arrière-plan
# ─────────────────────────────────────────────────────

@router.post("/ia/recharger")
async def recharger_manuel(
    background_tasks : BackgroundTasks,
    _                : None = Depends(verifier_api_key),
):
    """Force un ré-entraînement sans bloquer la réponse HTTP."""
    background_tasks.add_task(recharger_moteur)
    return {"message": "Rechargement lancé en arrière-plan.", "status": "ok"}


# ─────────────────────────────────────────────────────
# /api/ia/status
# ─────────────────────────────────────────────────────

@router.get("/ia/status")
def statut_moteur(_: None = Depends(verifier_api_key)):
    prochain = scheduler.get_job("recharger_moteur")
    return {
        "moteur_entraine":       moteur.nlp.est_entraine,
        "prochain_rechargement": str(
            prochain.next_run_time if prochain else "inconnu"
        ),
    }


# ─────────────────────────────────────────────────────
# /api/ia/health
# ─────────────────────────────────────────────────────

@router.get("/ia/health")
def health():
    return {
        "statut":          "ok",
        "version":         settings.VERSION,
        "modele_entraine": moteur.prix.est_entraine,
    }


# ─────────────────────────────────────────────────────
# /api/ia/recherche-contextuelle
# ─────────────────────────────────────────────────────

@router.post("/ia/recherche-contextuelle")
def recherche_contextuelle(
    requete : RequeteContextuelle,
    db      : Session = Depends(get_db),
    _       : None    = Depends(verifier_api_key),
):
    annonces = fetch_annonces(db)
    print("PHOTO ANNONCE 0:", annonces[0].get("photo") if annonces else "vide")

    lieu_geocode       = None
    lieux_culte        = []
    commodites_trouvees = {}

    if requete.lieu_reference:
        lieu_geocode = scoreur_contextuel.geocoder_lieu(
            requete.lieu_reference,
            requete.ville_reference or ""
        )

    if requete.religion and lieu_geocode:
        lieux_culte = scoreur_contextuel.chercher_lieux_proches(
            lat       = lieu_geocode["latitude"],
            lon       = lieu_geocode["longitude"],
            type_lieu = "place_of_worship",
            religion  = requete.religion,
            rayon_km  = requete.distance_culte_max or 3,
        )

    if requete.commodites and lieu_geocode:
        for commodite in requete.commodites:
            lieux = scoreur_contextuel.chercher_lieux_proches(
                lat       = lieu_geocode["latitude"],
                lon       = lieu_geocode["longitude"],
                type_lieu = commodite,
                rayon_km  = 5,
            )
            if lieux:
                commodites_trouvees[commodite] = lieux[:3]

    filtrees = moteur._appliquer_filtres(annonces, requete.dict())
    if not filtrees:
        return {
            "total":               0,
            "lieu_geocode":        lieu_geocode,
            "lieux_culte_trouves": lieux_culte,
            "annonces":            [],
        }

    scores_proximite = (
        scoreur_contextuel.scorer_annonces_par_contexte(
            filtrees,
            lieu_geocode["latitude"],
            lieu_geocode["longitude"],
        )
        if lieu_geocode
        else [0.5] * len(filtrees)
    )
    

    texte       = f"{requete.type_bien or ''} {requete.religion or ''}"
    scores_nlp  = moteur.nlp.calculer_similarite(texte, filtrees)
    scores_prix = moteur._score_prix(filtrees, requete.dict())
    scores_pop  = moteur._score_popularite(filtrees)

    resultats = []
    for i, annonce in enumerate(filtrees):

        annonce['score_contextuel'] = round(
    scores_proximite[i] * 0.40 + 
    scores_prix[i] * 0.30 +
    scores_nlp[i] * 0.30,
        )

        distance_ref = None
        if lieu_geocode and annonce.get("latitude") and annonce.get("longitude"):
            distance_ref = round(scoreur_contextuel._haversine(
                lieu_geocode["latitude"], lieu_geocode["longitude"],
                float(annonce["latitude"]), float(annonce["longitude"]),
            ), 2)

        analyse_quartier = {}
        if annonce.get("latitude") and annonce.get("longitude"):
            analyse_quartier = scoreur_contextuel.analyser_contexte_quartier(
                float(annonce["latitude"]), float(annonce["longitude"])
            )

        culte_annonce = []
        if requete.religion and annonce.get("latitude") and annonce.get("longitude"):
            culte_annonce = scoreur_contextuel.chercher_lieux_proches(
                lat       = float(annonce["latitude"]),
                lon       = float(annonce["longitude"]),
                type_lieu = "place_of_worship",
                religion  = requete.religion,
                rayon_km  = 2,
            )[:3]

        prediction = moteur.prix.predire_prix(
            ville            = annonce.get("ville", ""),
            categorie        = annonce.get("categorie", ""),
            surface_m2       = annonce.get("surface_m2") or 50,
            nb_chambres      = annonce.get("nb_chambres") or 1,
            meuble           = annonce.get("meuble") or False,
            type_transaction = annonce.get("type_transaction", "location"),
            quartier= annonce.get("quartier", "Inconnu"),
        )

        resultats.append({
            "id_annonce":          annonce["id"],
            "titre":               annonce["titre"],
            "prix":                float(annonce["prix"]),
            "categorie":           annonce["categorie"],
            "ville":               annonce["ville"],
            "quartier":            annonce.get("quartier"),
            "latitude":            annonce.get("latitude"),
            "longitude":           annonce.get("longitude"),
            "photo":               annonce.get("photo"),
            "score_final":         round(float(score_final), 4),
            "score_proximite":     round(float(scores_proximite[i]), 4),
            "score_nlp":           round(float(scores_nlp[i]), 4),
            "score_prix":          round(float(scores_prix[i]), 4),
            "distance_lieu_ref":   distance_ref,
            "lieux_culte_proches": culte_annonce,
            "analyse_quartier":    analyse_quartier,
            "commodites_proches":  commodites_trouvees,
            "prix_estime":         prediction.get("prix_estime"),
            "raison":              _generer_raison_contextuelle(
                score_final, scores_proximite[i], distance_ref,
                requete, culte_annonce, analyse_quartier,
            ),
        })

    resultats.sort(key=lambda x: x["score_final"], reverse=True)
    limite = requete.limite or 10

    return {
        "total":               len(resultats),
        "lieu_geocode":        lieu_geocode,
        "lieux_culte_trouves": lieux_culte,
        "commodites_trouvees": commodites_trouvees,
        "annonces":            resultats[:limite],
    }


# ─────────────────────────────────────────────────────
# /api/ia/analyser-quartier
# ─────────────────────────────────────────────────────

@router.get("/ia/analyser-quartier")
def analyser_quartier(
    lat : float,
    lon : float,
    _   : None = Depends(verifier_api_key),
):
    return scoreur_contextuel.analyser_contexte_quartier(lat, lon)

@router.post("/ia/recherche-nlp")
def recherche_nlp(
    requete : RequeteNLP,           # remplace par RequeteNLP si tu ajoutes le schema
    db      : Session = Depends(get_db),
    _       : None    = Depends(verifier_api_key),
):
    # description = requete.description
    # limite = requete.limite
    """
    Recherche par description en langage naturel.
    Flux :
      1. Extraction des critères structurés (regex NLP léger)
      2. Pré-filtrage SQL via fetch_annonces + filtres durs
      3. Scoring TF-IDF via moteur.nlp.calculer_similarite()
      4. Scoring prix via moteur._score_prix()
      5. Tri et retour avec criteres_extraits + mots_cles_nlp
    """
    description = requete.description.strip()
    limite = requete.limite or 12
    
    if len(description) < 5:
        raise HTTPException(status_code=400, detail="Description trop courte (min 5 caractères).")

    # ── 1. Extraire les critères depuis la description ──
    criteres = extraire_criteres_nlp(description)
    logger.info(f"[NLP] Critères extraits : {criteres}")

    # ── 2. Charger les annonces et pré-filtrer ──
    annonces = fetch_annonces(db)
    if not annonces:
        return {
            "criteres_extraits" : criteres,
            "mots_cles_nlp"     : [],
            "recommandations"   : [],
            "total"             : 0,
        }

    # Pré-filtrage dur (rapide, avant le NLP coûteux)
    filtrees = annonces
    if criteres.get("ville"):
        filtrees = [a for a in filtrees
                    if criteres["ville"].lower() in (a.get("ville") or "").lower()]
    if criteres.get("type_bien"):
        filtrees = [a for a in filtrees
                    if criteres["type_bien"].lower() in (a.get("categorie") or "").lower()]
    if criteres.get("type_transaction"):
        filtrees = [a for a in filtrees
                    if a.get("type_transaction") == criteres["type_transaction"]]
    if criteres.get("budget_max"):
        filtrees = [a for a in filtrees
                    if float(a.get("prix") or 0) <= criteres["budget_max"]]
    if criteres.get("nb_chambres"):
        filtrees = [a for a in filtrees
                    if int(a.get("nb_chambres") or 0) >= criteres["nb_chambres"]]
    if criteres.get("surface_min"):
        filtrees = [a for a in filtrees
                    if float(a.get("surface_m2") or 0) >= criteres["surface_min"]]
    if criteres.get("meuble"):
        filtrees = [a for a in filtrees if a.get("meuble")]

    # Si le pré-filtrage est trop restrictif, on élargit sur toutes les annonces
    if len(filtrees) < 3:
        logger.warning("[NLP] Pré-filtrage trop restrictif, élargissement.")
        filtrees = annonces[:100]

    # ── 3. Scoring TF-IDF (ton modèle NLPAnalyser) ──
    scores_nlp  = moteur.nlp.calculer_similarite(description, filtrees)

    # ── 4. Scoring prix ──
    scores_prix = moteur._score_prix(filtrees, criteres)

    # ── 5. Assembler et trier ──
    resultats = []
    for i, annonce in enumerate(filtrees):
        score_nlp   = float(scores_nlp[i])
        score_prix  = float(scores_prix[i])
        # Pondération : NLP 70% + Prix 30%
        score_final = round(score_nlp * 0.70 + score_prix * 0.30, 4)

        # Prédiction prix via ton modèle ML
        prediction = moteur.prix.predire_prix(
            ville            = annonce.get("ville", ""),
            categorie        = annonce.get("categorie", ""),
            surface_m2       = annonce.get("surface_m2") or 50,
            nb_chambres      = annonce.get("nb_chambres") or 1,
            meuble           = annonce.get("meuble") or False,
            type_transaction = annonce.get("type_transaction", "location"),
            quartier         = annonce.get("quartier") or "Inconnu",
        )

        # Raison lisible
        raisons = []
        if score_nlp > 0.25:
            raisons.append("correspond à votre description")
        if criteres.get("ville") and criteres["ville"].lower() in (annonce.get("ville") or "").lower():
            raisons.append(f"situé à {criteres['ville']}")
        if criteres.get("budget_max") and float(annonce.get("prix") or 0) <= criteres["budget_max"]:
            raisons.append("dans votre budget")
        if annonce.get("meuble") and criteres.get("meuble"):
            raisons.append("meublé")
        if criteres.get("nb_chambres") and int(annonce.get("nb_chambres") or 0) >= criteres["nb_chambres"]:
            raisons.append(f"{annonce.get('nb_chambres')} chambre(s)")

        resultats.append({
            "id_annonce"  : annonce["id"],
            "titre"       : annonce["titre"],
            "prix"        : float(annonce["prix"]),
            "categorie"   : annonce.get("categorie"),
            "ville"       : annonce.get("ville"),
            "quartier"    : annonce.get("quartier"),
            "latitude"    : annonce.get("latitude"),
            "longitude"   : annonce.get("longitude"),
            "photo"       : annonce.get("photo"),
            "nb_chambres" : annonce.get("nb_chambres"),
            "surface_m2"  : annonce.get("surface_m2"),
            "meuble"      : annonce.get("meuble"),
            "score_final" : score_final,
            "score_nlp"   : round(score_nlp, 4),
            "score_prix"  : round(score_prix, 4),
            "prix_estime" : prediction.get("prix_estime"),
            "raison"      : ("Ce bien " + ", ".join(raisons) + ".") if raisons
                            else "Correspond à votre recherche.",
        })

    # Tri par score décroissant
    resultats.sort(key=lambda x: x["score_final"], reverse=True)

    # Mots-clés extraits par ton modèle TF-IDF
    mots_cles = moteur.nlp.extraire_mots_cles(description, n=6)

    return {
        "criteres_extraits" : criteres,
        "mots_cles_nlp"     : mots_cles,
        "recommandations"   : resultats[:limite],
        "total"             : len(resultats),
    }




# ─────────────────────────────────────────────────────
# Helper : raison contextuelle
# ─────────────────────────────────────────────────────

def _generer_raison_contextuelle(score, score_prox, distance,
                                  requete, culte, quartier) -> str:
    raisons = []

    if distance is not None:
        if distance <= 1:
            raisons.append(f"à seulement {distance} km de {requete.lieu_reference or 'votre lieu de référence'}")
        elif distance <= 3:
            raisons.append(f"à {distance} km de {requete.lieu_reference or 'votre lieu de référence'}")

    if requete.religion and culte:
        raisons.append(f"{len(culte)} lieu(x) de culte {requete.religion} à moins de 2 km")

    if quartier.get("ecoles", 0) > 0:
        raisons.append(f"{quartier['ecoles']} école(s) à proximité")

    if quartier.get("hopitaux", 0) > 0:
        raisons.append("hôpital proche")

    if quartier.get("marches", 0) > 0:
        raisons.append("marché à proximité")

    if not raisons:
        raisons.append("correspond à vos critères")

    return "Ce bien " + ", ".join(raisons) + "."



def extraire_criteres_nlp(texte: str) -> dict:
    """
    Extrait les critères structurés depuis une description libre.
    Utilisé AVANT le TF-IDF pour pré-filtrer les annonces en base.
    """
    t = texte.lower()
    criteres = {}

    # ── Type de bien ──
    types_bien = {
        'appartement' : ['appartement', 'appart'],
        'studio'      : ['studio', 'f1'],
        'villa'       : ['villa', 'maison', 'duplex', 'triplex'],
        'chambre'     : ['chambre seule', 'chambre individuelle'],
        'bureau'      : ['bureau', 'local commercial', 'boutique', 'magasin'],
        'terrain'     : ['terrain', 'parcelle'],
    }
    for type_bien, mots in types_bien.items():
        if any(m in t for m in mots):
            criteres['type_bien'] = type_bien
            break

    # ── Ville ──
    villes = {
        'Douala'    : ['douala', 'dla'],
        'Yaounde'   : ['yaounde', 'yaoundé', 'capitale'],
        'Bafoussam' : ['bafoussam', 'baf'],
        'Garoua'    : ['garoua'],
        'Bamenda'   : ['bamenda'],
    }
    for ville, mots in villes.items():
        if any(m in t for m in mots):
            criteres['ville'] = ville
            break

    # ── Quartier ──
    quartiers = {
        'Bonamoussadi' : ['bonamoussadi', 'bonams'],
        'Akwa'         : ['akwa'],
        'Bonapriso'    : ['bonapriso'],
        'Makepe'       : ['makepe'],
        'Deido'        : ['deido'],
        'Bepanda'      : ['bepanda'],
        'Logpom'       : ['logpom'],
        'Bastos'       : ['bastos'],
        'Nlongkak'     : ['nlongkak'],
    }
    for quartier, mots in quartiers.items():
        if any(m in t for m in mots):
            criteres['quartier'] = quartier
            break

    # ── Transaction ──
    if any(m in t for m in ['louer', 'location', 'à louer', 'mensuel', 'loyer']):
        criteres['type_transaction'] = 'location'
    elif any(m in t for m in ['acheter', 'achat', 'vente', 'à vendre', 'acquisition']):
        criteres['type_transaction'] = 'vente'

    # ── Budget ──
    patterns_budget = [
        (r'(\d[\d\s]*)[\s]*(?:f\s*cfa|fcfa|cfa|xaf|francs?)', False),
        (r'(\d+)\s*k\b',                                        True),   # 150k → 150000
        (r'moins\s*de\s*(\d[\d\s]*)',                           False),
        (r'budget\s*(?:de|:)?\s*(\d[\d\s]*)',                   False),
        (r'(\d{2,})\s*000',                                     False),
    ]
    for pattern, is_k in patterns_budget:
        m = re.search(pattern, t, re.IGNORECASE)
        if m:
            val = int(m.group(1).replace(' ', ''))
            if is_k and val < 10000:
                val *= 1000
            if 1000 < val < 100_000_000:
                criteres['budget_max'] = val
                break

    # Mots signalant budget bas → plafond par défaut 150k
    if 'budget_max' not in criteres:
        if any(m in t for m in ['pas cher', 'abordable', 'économique', 'bon marché', 'modeste']):
            criteres['budget_max'] = 150_000

    # ── Chambres ──
    for pattern in [r'(\d+)\s*(?:chambre|pièce|piece)', r'f(\d)\b', r'(\d)\s*ch\b']:
        m = re.search(pattern, t, re.IGNORECASE)
        if m:
            nb = int(m.group(1))
            if 1 <= nb <= 10:
                criteres['nb_chambres'] = nb
                break

    # ── Surface ──
    m = re.search(r'(\d+)\s*m[²2]', t, re.IGNORECASE)
    if m:
        s = int(m.group(1))
        if 10 < s < 2000:
            criteres['surface_min'] = s

    # ── Caractéristiques booléennes ──
    if any(m in t for m in ['meublé', 'meuble', 'équipé', 'avec meubles']):
        criteres['meuble'] = True

    return criteres

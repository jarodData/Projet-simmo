import numpy as np
from models.nlp_analyser     import NLPAnalyser
from models.prix_predictor   import PrixPredictor
from models.scoring_distance import ScoringDistance
from models.photo_selector import PhotoSelector
from utils.photo_pipeline import appliquer_meilleure_photo
class MoteurHybride:

    def __init__(self):
        self.nlp      = NLPAnalyser()
        self.prix     = PrixPredictor()
        self.distance = ScoringDistance()

    def entrainer(self, annonces):
        print("Entrainement des modeles IA...")
        self.nlp.entrainer_sur_corpus(annonces)
        self.prix.entrainer(annonces)
        print("Tous les modeles sont prets.")

    
    def recommander(self, annonces, requete):
        if not annonces:
            return []
    def recommander(self, annonces, requete):

        if not annonces:
            return []
        annonces =appliquer_meilleure_photo(annonces)

        filtrees = self._appliquer_filtres(annonces, requete)

        if not filtrees:
            return []

        texte_requete  = self._construire_texte_requete(requete)
        scores_nlp     = self.nlp.calculer_similarite(
            texte_requete, filtrees
        )
        scores_dist    = self.distance.calculer_score(
            requete.get('latitude'),
            requete.get('longitude'),
            filtrees
        )
        scores_prix    = self._calculer_score_prix(
            filtrees, requete
        )
        scores_pop     = self._calculer_score_popularite(filtrees)

        resultats = [] 

        for i, annonce in enumerate(filtrees):
            photo = ""

            if annonce.get("photo_principale"):
                photo = annonce["photo_principale"].get("chemin_image", "")
            photo = annonce.get("photo")
            
            prediction = self.prix.predire_prix(
                ville            = annonce.get('ville', ''),
                categorie        = annonce.get('categorie', ''),
                surface_m2       = annonce.get('surface_m2') or 50,
                nb_chambres      = annonce.get('nb_chambres') or 1,
                meuble           = annonce.get('meuble') or False,
                type_transaction = annonce.get('type_transaction', 'location'),
            )

            score_final = (
                scores_nlp[i]  * 0.35 +
                scores_prix[i] * 0.30 +
                scores_dist[i] * 0.20 +
                scores_pop[i]  * 0.15
            )

            resultats.append({
                "id_annonce": annonce["id"],
                "titre": annonce["titre"],
                "prix": float(annonce["prix"]),
                "categorie": annonce.get("categorie", ""),
                "ville": annonce.get("ville", ""),
                "quartier": annonce.get("quartier"),

                "photo": photo,

                "score_final": round(float(score_final), 4),
                "score_nlp": round(float(scores_nlp[i]), 4),
                "score_prix": round(float(scores_prix[i]), 4),
                "score_distance": round(float(scores_dist[i]), 4),
                "score_popularite": round(float(scores_pop[i]), 4),

                "prix_estime": prediction.get("prix_estime"),

                "raison": self._generer_raison(
                    scores_nlp[i],
                    scores_prix[i],
                    scores_dist[i],
                    annonce
                ),
            })

        resultats.sort(
            key=lambda x: x['score_final'],
            reverse=True
        )
        limite = requete.get('limite', 10)
        return resultats[:limite]
    
    
    def appliquer_meilleure_photo(annonces):

        selector = PhotoSelector()

        for annonce in annonces:

            photos = annonce.get("photos", [])

            annonce["photo"] = selector.choisir_meilleure(photos)

        return annonces
    

    def _appliquer_filtres(self, annonces, requete):
        filtrees = annonces[:]

        if requete.get('type_transaction'):
            filtrees = [
                a for a in filtrees
                if a.get('type_transaction') ==
                   requete['type_transaction']
            ]
        if requete.get('type_bien'):
            filtrees = [
                a for a in filtrees
                if a.get('categorie') == requete['type_bien']
            ]
        if requete.get('budget_max'):
            filtrees = [
                a for a in filtrees
                if float(a.get('prix', 0)) <= requete['budget_max']
            ]
        if requete.get('budget_min'):
            filtrees = [
                a for a in filtrees
                if float(a.get('prix', 0)) >= requete['budget_min']
            ]
        if requete.get('surface_min'):
            filtrees = [
                a for a in filtrees
                if a.get('surface_m2') and
                   float(a['surface_m2']) >= requete['surface_min']
            ]
        if requete.get('nb_chambres'):
            filtrees = [
                a for a in filtrees
                if a.get('nb_chambres') and
                   int(a['nb_chambres']) >= requete['nb_chambres']
            ]
        if requete.get('ville'):
            ville_lower = requete['ville'].lower()
            filtrees = [
                a for a in filtrees
                if a.get('ville') and
                   ville_lower in a['ville'].lower()
            ]
        if requete.get('meuble') is True:
            filtrees = [
                a for a in filtrees
                if bool(a.get('meuble'))
            ]

        return filtrees

    def _construire_texte_requete(self, requete):
        parties = []
        if requete.get('type_bien'):
            parties.append(requete['type_bien'])
        if requete.get('ville'):
            parties.append(requete['ville'])
        if requete.get('nb_chambres'):
            parties.append(
                str(requete['nb_chambres']) + ' chambres'
            )
        if requete.get('meuble'):
            parties.append('meuble')
        if requete.get('type_transaction'):
            parties.append(requete['type_transaction'])
        if requete.get('q'):
            parties.append(requete['q'])
        return ' '.join(parties) if parties else 'logement'

    def _calculer_score_prix(self, annonces, requete):
        scores     = []
        budget_max = requete.get('budget_max')
        budget_min = requete.get('budget_min')

        for annonce in annonces:
            prix = float(annonce.get('prix', 0))
            if budget_max and budget_min:
                cible = (budget_max + budget_min) / 2
                ecart = abs(prix - cible) / cible if cible else 0
                score = max(0, 1 - ecart)
            elif budget_max:
                ratio = prix / budget_max if budget_max else 1
                score = 1.0 if ratio <= 0.8 else max(
                    0, 1 - (ratio - 0.8) * 5
                )
            else:
                score = 0.5
            scores.append(round(score, 4))

        return scores

    def _calculer_score_popularite(self, annonces):
        vues     = [int(a.get('vues', 0)) for a in annonces]
        max_vues = max(vues) if vues and max(vues) > 0 else 1
        return [round(v / max_vues, 4) for v in vues]

    def _generer_raison(self, score_nlp, score_prix,
                        score_dist, annonce):
        raisons = []
        if score_nlp > 0.6:
            raisons.append('correspond a votre recherche')
        if score_prix > 0.7:
            raisons.append('dans votre budget')
        if score_dist > 0.7:
            raisons.append('proche de votre localisation')
        if int(annonce.get('vues', 0)) > 50:
            raisons.append('tres populaire')
        if not raisons:
            raisons.append('suggere pour vous')
        return 'Ce bien ' + ', '.join(raisons) + '.'
import numpy as np
from models.nlp_analyser     import NLPAnalyser
from models.prix_predictor   import PrixPredictor
from models.scoring_distance import ScoringDistance

class MoteurHybride:
    def __init__(self):
        self.nlp      = NLPAnalyser()
        self.prix     = PrixPredictor()
        self.distance = ScoringDistance()

    def entrainer(self, annonces: list):
        """Entraîne tous les modèles"""
        print(" Entraînement des modèles IA...")

        #  Entraîner le NLP sur le corpus des annonces
        self.nlp.entrainer_sur_corpus(annonces)
        print(" Modèle NLP entraîné sur le corpus !")

        # Entraîner le prédicteur de prix
        self.prix.entrainer(annonces)

        print(" Tous les modèles sont prêts !")

    def recommander(self, annonces: list, requete: dict) -> list:
        """Moteur de recommandation hybride"""

        if not annonces:
            return []

        # ── 1. Filtres stricts ──────────────
        filtrees = self._appliquer_filtres(annonces, requete)

        if not filtrees:
            return []

        # ── 2. Scores NLP ───────────────────
        texte_requete = self._construire_texte_requete(requete)
        scores_nlp    = self.nlp.calculer_similarite(
            texte_requete, filtrees
        )

        # ── 3. Scores Distance ──────────────
        scores_dist = self.distance.calculer_score(
            requete.get('latitude'),
            requete.get('longitude'),
            filtrees
        )

        # ── 4. Scores Prix ──────────────────
        scores_prix = self._calculer_score_prix(filtrees, requete)

        # ── 5. Scores Popularité ────────────
        scores_pop = self._calculer_score_popularite(filtrees)

        # ── 6. Score Final Hybride ──────────
        resultats = []
        for i, annonce in enumerate(filtrees):

            score_final = (
                scores_nlp[i]  * 0.35 +
                scores_prix[i] * 0.30 +
                scores_dist[i] * 0.20 +
                scores_pop[i]  * 0.15
            )

            # Prédire le prix
            prediction = self.prix.predire_prix(
                ville            = annonce.get('ville', ''),
                categorie        = annonce.get('categorie', ''),
                surface_m2       = annonce.get('surface_m2') or 50,
                nb_chambres      = annonce.get('nb_chambres') or 1,
                meuble           = annonce.get('meuble') or False,
                type_transaction = annonce.get('type_transaction', 'location'),
            )

                    # Chercher la photo dans l'annonce
        photo = annonce.get('photo')
        if photo is None or str(photo) == 'None' or str(photo) == '':
            photo = None

        resultats.append({
            'id_annonce'      : annonce['id'],
            'titre'           : annonce['titre'],
            'prix'            : float(annonce['prix']),
            'categorie'       : annonce['categorie'],
            'ville'           : annonce['ville'],
            'quartier'        : annonce.get('quartier'),
            'photo'           : photo,  # ✅ None propre
            'score_final'     : round(float(score_final), 4),
            'score_nlp'       : round(float(scores_nlp[i]), 4),
            'score_prix'      : round(float(scores_prix[i]), 4),
            'score_distance'  : round(float(scores_dist[i]), 4),
            'score_popularite': round(float(scores_pop[i]), 4),
            'prix_estime'     : prediction.get('prix_estime'),
            'raison'          : self._generer_raison(
                scores_nlp[i], scores_prix[i],
                scores_dist[i], annonce
            ),
        })
        # Trier par score décroissant
        resultats.sort(key=lambda x: x['score_final'], reverse=True)

        limite = requete.get('limite', 10)
        return resultats[:limite]

    # ── Méthodes privées ────────────────────

    def _appliquer_filtres(self, annonces, requete) -> list:
        filtrees = annonces[:]

        if requete.get('type_transaction'):
            filtrees = [
                a for a in filtrees
                if a['type_transaction'] == requete['type_transaction']
            ]

        if requete.get('type_bien'):
            filtrees = [
                a for a in filtrees
                if a['categorie'] == requete['type_bien']
            ]

        if requete.get('budget_max'):
            filtrees = [
                a for a in filtrees
                if float(a['prix']) <= requete['budget_max']
            ]

        if requete.get('budget_min'):
            filtrees = [
                a for a in filtrees
                if float(a['prix']) >= requete['budget_min']
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
            filtrees = [
                a for a in filtrees
                if requete['ville'].lower() in a['ville'].lower()
            ]

        if requete.get('meuble') is not None:
            filtrees = [
                a for a in filtrees
                if bool(a.get('meuble')) == requete['meuble']
            ]

        return filtrees

    def _construire_texte_requete(self, requete: dict) -> str:
        parties = []
        if requete.get('type_bien'):
            parties.append(requete['type_bien'])
        if requete.get('ville'):
            parties.append(f"à {requete['ville']}")
        if requete.get('nb_chambres'):
            parties.append(f"{requete['nb_chambres']} chambres")
        if requete.get('meuble'):
            parties.append('meublé')
        if requete.get('type_transaction'):
            parties.append(requete['type_transaction'])
        if requete.get('q'):
            parties.append(requete['q'])
        return ' '.join(parties) if parties else 'logement'

    def _calculer_score_prix(self, annonces, requete) -> list:
        scores    = []
        budget_max = requete.get('budget_max')
        budget_min = requete.get('budget_min')

        for annonce in annonces:
            prix = float(annonce['prix'])

            if budget_max and budget_min:
                budget_cible = (budget_max + budget_min) / 2
                ecart = abs(prix - budget_cible) / budget_cible
                score = max(0, 1 - ecart)
            elif budget_max:
                ratio = prix / budget_max
                score = 1.0 if ratio <= 0.8 else max(
                    0, 1 - (ratio - 0.8) * 5
                )
            else:
                score = 0.5

            scores.append(round(score, 4))

        return scores

    def _calculer_score_popularite(self, annonces) -> list:
        vues     = [int(a.get('vues', 0)) for a in annonces]
        max_vues = max(vues) if max(vues) > 0 else 1
        return [round(v / max_vues, 4) for v in vues]

    def _generer_raison(self, score_nlp, score_prix,
                        score_dist, annonce) -> str:
        raisons = []

        if score_nlp > 0.6:
            raisons.append("correspond bien à votre recherche")
        if score_prix > 0.7:
            raisons.append("dans votre budget")
        if score_dist > 0.7:
            raisons.append("proche de votre localisation")
        if int(annonce.get('vues', 0)) > 50:
            raisons.append("très populaire")

        if not raisons:
            raisons.append("suggéré pour vous")

        return "Ce bien " + ", ".join(raisons) + "."
    
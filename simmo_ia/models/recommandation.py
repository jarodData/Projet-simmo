# ============================================
# MODELE DE RECOMMANDATION — SIMMo IA
# Filtrage hybride :
#   - NLP (TF-IDF + cosinus)
#   - Prediction de prix (Random Forest)
#   - Scoring distance (Haversine)
#   - Scoring popularite
# ============================================

import numpy as np
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import LabelEncoder
from sklearn.model_selection import train_test_split
import math
import re


class RecommandationNLP:
    """
    Analyse le contenu textuel des annonces
    via TF-IDF et similarite cosinus.
    Fonctionne en francais sans PyTorch.
    """

    MOTS_VIDES = [
        'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de',
        'et', 'ou', 'mais', 'donc', 'car', 'ni', 'or',
        'ce', 'cet', 'cette', 'ces', 'mon', 'ma', 'mes',
        'ton', 'ta', 'tes', 'son', 'sa', 'ses', 'notre',
        'votre', 'leur', 'leurs', 'que', 'qui', 'quoi',
        'dont', 'est', 'sont', 'avec', 'sans', 'sur',
        'sous', 'dans', 'par', 'pour', 'en', 'au', 'aux',
        'se', 'si', 'ne', 'pas', 'plus', 'tres', 'bien',
        'il', 'elle', 'ils', 'elles', 'je', 'tu', 'nous',
        'vous', 'on', 'me', 'te', 'lui', 'tout', 'tous',
    ]

    def __init__(self):
        self.vectorizer = TfidfVectorizer(
            ngram_range   = (1, 2),
            max_features  = 5000,
            strip_accents = 'unicode',
            analyzer      = 'word',
            stop_words    = self.MOTS_VIDES,
            sublinear_tf  = True,
        )
        self.est_entraine = False

    def nettoyer_texte(self, texte):
        if not texte:
            return ''
        texte = str(texte).lower()
        texte = re.sub(r'[^\w\s]', ' ', texte)
        texte = re.sub(r'\b\d+\b', '', texte)
        texte = re.sub(r'\s+', ' ', texte).strip()
        return texte

    def enrichir_texte(self, annonce):
        parties = []
        if annonce.get('titre'):
            parties.append(annonce['titre'])
            parties.append(annonce['titre'])
        if annonce.get('description'):
            parties.append(annonce['description'])
        if annonce.get('categorie'):
            parties.append(annonce['categorie'])
        if annonce.get('ville'):
            parties.append(annonce['ville'])
        if annonce.get('quartier'):
            parties.append(annonce['quartier'])
        if annonce.get('meuble'):
            parties.append('meuble')
        if annonce.get('nb_chambres'):
            parties.append(
                str(annonce['nb_chambres']) + ' chambres'
            )
        if annonce.get('type_transaction'):
            parties.append(annonce['type_transaction'])
        return self.nettoyer_texte(' '.join(parties))

    def entrainer_sur_corpus(self, annonces):
        if not annonces:
            return
        corpus = [self.enrichir_texte(a) for a in annonces]
        corpus_valides = [t for t in corpus if t.strip()]
        if corpus_valides:
            self.vectorizer.fit(corpus_valides)
            self.est_entraine = True
            print("Modele NLP entraine sur",
                  len(corpus_valides), "annonces.")

    def calculer_similarite(self, texte_requete, annonces):
        if not texte_requete or not annonces:
            return [0.5] * len(annonces)

        texte_requete = self.nettoyer_texte(texte_requete)
        if not texte_requete:
            return [0.5] * len(annonces)

        textes_annonces = [
            self.enrichir_texte(a) for a in annonces
        ]

        try:
            tous_textes = [texte_requete] + textes_annonces
            tous_textes = [
                t if t.strip() else 'vide'
                for t in tous_textes
            ]

            if not self.est_entraine:
                self.vectorizer.fit(tous_textes)

            matrice           = self.vectorizer.transform(tous_textes)
            vecteur_requete   = matrice[0]
            vecteurs_annonces = matrice[1:]

            scores = cosine_similarity(
                vecteur_requete,
                vecteurs_annonces
            )[0]

            return [float(s) for s in scores]

        except Exception as e:
            print("Erreur NLP:", e)
            return [0.5] * len(annonces)

    def analyser_qualite_description(self, description):
        if not description:
            return 0.0

        description = str(description).lower()

        score_longueur = min(len(description) / 500, 1.0)
        nb_mots        = len(description.split())
        score_mots     = min(nb_mots / 80, 1.0)

        mots_positifs = [
            'lumineux', 'calme', 'moderne', 'renove', 'parking',
            'securise', 'climatise', 'gardien', 'piscine',
            'terrasse', 'balcon', 'jardin', 'cuisine', 'proche',
            'meuble', 'standing', 'spacieux', 'confortable',
            'neuf', 'securite', 'eau', 'electricite', 'wifi',
        ]
        nb_positifs    = sum(
            1 for mot in mots_positifs
            if mot in description
        )
        score_positifs = min(nb_positifs / 6, 1.0)

        a_ponctuation  = '.' in description or ',' in description
        score_structure = 0.3 if a_ponctuation else 0.0

        score = (
            score_longueur  * 0.30 +
            score_mots      * 0.25 +
            score_positifs  * 0.35 +
            score_structure * 0.10
        )

        return round(min(score, 1.0), 4)


class RecommandationPrix:
    """
    Predit le prix d'un bien immobilier
    via Random Forest Regressor.
    """

    def __init__(self):
        self.modele          = RandomForestRegressor(
            n_estimators = 100,
            random_state = 42,
            n_jobs       = -1,
        )
        self.encodeur_ville  = LabelEncoder()
        self.encodeur_cat    = LabelEncoder()
        self.encodeur_trans  = LabelEncoder()
        self.est_entraine    = False

    def preparer_features(self, annonces):
        df = pd.DataFrame(annonces)
        mediane_surface  = df['surface_m2'].median() \
            if 'surface_m2' in df.columns else 50
        df['surface_m2'] = df['surface_m2'].fillna(mediane_surface)
        df['nb_chambres'] = df['nb_chambres'].fillna(1)
        df['meuble']      = df['meuble'].fillna(False).astype(int)
        return df

    def entrainer(self, annonces):
        if len(annonces) < 10:
            print("Pas assez de donnees pour entrainer le modele prix.",
                  len(annonces), "annonces disponibles.")
            return

        df = self.preparer_features(annonces)

        df['ville_enc'] = self.encodeur_ville.fit_transform(
            df['ville'].fillna('Inconnue')
        )
        df['cat_enc']   = self.encodeur_cat.fit_transform(
            df['categorie'].fillna('appartement')
        )
        df['trans_enc'] = self.encodeur_trans.fit_transform(
            df['type_transaction'].fillna('location')
        )

        features = [
            'surface_m2', 'nb_chambres', 'meuble',
            'ville_enc', 'cat_enc', 'trans_enc',
        ]
        X = df[features].values
        y = df['prix'].values

        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        self.modele.fit(X_train, y_train)
        self.est_entraine = True

        score = self.modele.score(X_test, y_test)
        print("Modele prix entraine. Score R2:", round(score, 2))

    def predire_prix(self, ville, categorie, surface_m2,
                     nb_chambres, meuble, type_transaction):
        if not self.est_entraine:
            return {
                'prix_estime'   : None,
                'fourchette_min': None,
                'fourchette_max': None,
                'fiabilite'     : 'indisponible',
            }

        try:
            ville_enc = self.encodeur_ville.transform(
                [ville]
            )[0] if ville in self.encodeur_ville.classes_ else 0

            cat_enc   = self.encodeur_cat.transform(
                [categorie]
            )[0] if categorie in self.encodeur_cat.classes_ else 0

            trans_enc = self.encodeur_trans.transform(
                [type_transaction]
            )[0] if type_transaction in \
                self.encodeur_trans.classes_ else 0

            X = np.array([[
                surface_m2,
                nb_chambres,
                int(meuble),
                ville_enc,
                cat_enc,
                trans_enc,
            ]])

            prix_predit = self.modele.predict(X)[0]

            return {
                'prix_estime'   : round(float(prix_predit), 2),
                'fourchette_min': round(float(prix_predit * 0.85), 2),
                'fourchette_max': round(float(prix_predit * 1.15), 2),
                'fiabilite'     : 'haute',
            }

        except Exception as e:
            print("Erreur prediction prix:", e)
            return {
                'prix_estime'   : None,
                'fourchette_min': None,
                'fourchette_max': None,
                'fiabilite'     : 'erreur',
            }


class RecommandationDistance:
    """
    Calcule le score de proximite geographique
    via la formule de Haversine.
    """

    @staticmethod
    def haversine(lat1, lon1, lat2, lon2):
        R    = 6371
        lat1 = math.radians(float(lat1))
        lon1 = math.radians(float(lon1))
        lat2 = math.radians(float(lat2))
        lon2 = math.radians(float(lon2))
        dlat = lat2 - lat1
        dlon = lon2 - lon1
        a    = (math.sin(dlat / 2) ** 2 +
                math.cos(lat1) * math.cos(lat2) *
                math.sin(dlon / 2) ** 2)
        return R * 2 * math.asin(math.sqrt(a))

    def calculer_score(self, lat_user, lon_user, annonces):
        scores = []
        for annonce in annonces:
            lat_a = annonce.get('latitude')
            lon_a = annonce.get('longitude')

            if not lat_a or not lon_a or \
               not lat_user or not lon_user:
                scores.append(0.5)
                continue

            try:
                distance = self.haversine(
                    lat_user, lon_user,
                    float(lat_a), float(lon_a)
                )

                if   distance <= 0.5: score = 1.0
                elif distance <= 1:   score = 0.9
                elif distance <= 2:   score = 0.75
                elif distance <= 3:   score = 0.6
                elif distance <= 5:   score = 0.4
                elif distance <= 10:  score = 0.2
                else:                 score = 0.0

                scores.append(round(score, 4))

            except Exception:
                scores.append(0.5)

        return scores

    def filtrer_par_rayon(self, lat_user, lon_user,
                           annonces, rayon_km=10):
        if not lat_user or not lon_user:
            return annonces

        resultat = []
        for a in annonces:
            if not a.get('latitude') or not a.get('longitude'):
                resultat.append(a)
                continue
            try:
                dist = self.haversine(
                    lat_user, lon_user,
                    float(a['latitude']),
                    float(a['longitude'])
                )
                if dist <= rayon_km:
                    resultat.append(a)
            except Exception:
                resultat.append(a)

        return resultat


class MoteurRecommandation:
    """
    Moteur hybride combinant :
    - NLP      (35%)
    - Prix     (30%)
    - Distance (20%)
    - Popularite (15%)
    """

    def __init__(self):
        self.nlp      = RecommandationNLP()
        self.prix     = RecommandationPrix()
        self.distance = RecommandationDistance()

    def entrainer(self, annonces):
        print("Entrainement de tous les modeles...")
        self.nlp.entrainer_sur_corpus(annonces)
        self.prix.entrainer(annonces)
        print("Tous les modeles sont prets.")

    def recommander(self, annonces, requete):
        if not annonces:
            return []

        filtrees = self._appliquer_filtres(annonces, requete)
        if not filtrees:
            return []

        texte_requete  = self._construire_texte(requete)
        scores_nlp     = self.nlp.calculer_similarite(
            texte_requete, filtrees
        )
        scores_dist    = self.distance.calculer_score(
            requete.get('latitude'),
            requete.get('longitude'),
            filtrees
        )
        scores_prix    = self._score_prix(filtrees, requete)
        scores_pop     = self._score_popularite(filtrees)

        resultats = []
        for i, annonce in enumerate(filtrees):

            score_final = (
                scores_nlp[i]  * 0.35 +
                scores_prix[i] * 0.30 +
                scores_dist[i] * 0.20 +
                scores_pop[i]  * 0.15
            )

            prediction = self.prix.predire_prix(
                ville            = annonce.get('ville', ''),
                categorie        = annonce.get('categorie', ''),
                surface_m2       = annonce.get('surface_m2') or 50,
                nb_chambres      = annonce.get('nb_chambres') or 1,
                meuble           = annonce.get('meuble') or False,
                type_transaction = annonce.get(
                    'type_transaction', 'location'
                ),
            )

            # Gestion photo propre
            photo = annonce.get('photo')
            if photo is None or \
               str(photo) in ('None', 'null', ''):
                photo = None

            resultats.append({
                'id_annonce'      : annonce['id'],
                'titre'           : annonce['titre'],
                'prix'            : float(annonce['prix']),
                'categorie'       : annonce.get('categorie', ''),
                'ville'           : annonce.get('ville', ''),
                'quartier'        : annonce.get('quartier'),
                'photo'           : photo,
                'score_final'     : round(float(score_final), 4),
                'score_nlp'       : round(float(scores_nlp[i]), 4),
                'score_prix'      : round(float(scores_prix[i]), 4),
                'score_distance'  : round(float(scores_dist[i]), 4),
                'score_popularite': round(float(scores_pop[i]), 4),
                'prix_estime'     : prediction.get('prix_estime'),
                'raison'          : self._raison(
                    scores_nlp[i],
                    scores_prix[i],
                    scores_dist[i],
                    annonce
                ),
            })

        resultats.sort(
            key     = lambda x: x['score_final'],
            reverse = True
        )
        limite = requete.get('limite', 10)
        return resultats[:limite]

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
                if float(a.get('prix', 0)) <=
                   requete['budget_max']
            ]
        if requete.get('budget_min'):
            filtrees = [
                a for a in filtrees
                if float(a.get('prix', 0)) >=
                   requete['budget_min']
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
            filtrees    = [
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

    def _construire_texte(self, requete):
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

    def _score_prix(self, annonces, requete):
        scores     = []
        budget_max = requete.get('budget_max')
        budget_min = requete.get('budget_min')

        for annonce in annonces:
            prix = float(annonce.get('prix', 0))

            if budget_max and budget_min:
                cible = (budget_max + budget_min) / 2
                ecart = abs(prix - cible) / cible if cible else 0
                score = max(0.0, 1.0 - ecart)
            elif budget_max:
                ratio = prix / budget_max if budget_max else 1
                score = 1.0 if ratio <= 0.8 else max(
                    0.0, 1.0 - (ratio - 0.8) * 5
                )
            else:
                score = 0.5

            scores.append(round(score, 4))

        return scores

    def _score_popularite(self, annonces):
        vues     = [int(a.get('vues', 0)) for a in annonces]
        max_vues = max(vues) if vues and max(vues) > 0 else 1
        return [round(v / max_vues, 4) for v in vues]

    def _raison(self, score_nlp, score_prix,
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
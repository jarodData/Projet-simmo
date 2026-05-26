import numpy as np
import re
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

class NLPAnalyser:
    def __init__(self):
        print("⏳ Initialisation du modèle NLP (TF-IDF)...")
        self.vectorizer = TfidfVectorizer(
            # Paramètres optimisés pour le français
            ngram_range    = (1, 2),  
            max_features   = 5000,
            strip_accents  = 'unicode',
            analyzer       = 'word',
            stop_words     = self._mots_vides_francais(),
            sublinear_tf   = True,
        )
        
        self.est_entraine = False
        self.corpus       = []
        print(" Modèle NLP prêt !")

    def _mots_vides_francais(self):
        """Mots vides français à ignorer"""
        return [
            'le', 'la', 'les', 'un', 'une', 'des', 'du', 'de',
            'et', 'ou', 'mais', 'donc', 'car', 'ni', 'or',
            'ce', 'cet', 'cette', 'ces', 'mon', 'ma', 'mes',
            'ton', 'ta', 'tes', 'son', 'sa', 'ses', 'notre',
            'votre', 'leur', 'leurs', 'que', 'qui', 'quoi',
            'dont', 'où', 'est', 'sont', 'être', 'avoir',
            'avec', 'sans', 'sur', 'sous', 'dans', 'par',
            'pour', 'en', 'au', 'aux', 'se', 'si', 'ne',
            'pas', 'plus', 'très', 'bien', 'tout', 'tous',
            'il', 'elle', 'ils', 'elles', 'je', 'tu', 'nous',
            'vous', 'on', 'me', 'te', 'lui',
        ]

    def _nettoyer_texte(self, texte: str) -> str:
        """Nettoie et normalise un texte"""
        if not texte:
            return ''
        # Minuscules
        texte = texte.lower()
        # Supprimer les caractères spéciaux
        texte = re.sub(r'[^\w\s]', ' ', texte)
        # Supprimer les chiffres isolés
        texte = re.sub(r'\b\d+\b', '', texte)
        # Espaces multiples
        texte = re.sub(r'\s+', ' ', texte).strip()
        return texte

    def _enrichir_texte(self, annonce: dict) -> str:
        """
        Enrichit le texte d'une annonce avec tous ses attributs
        pour améliorer la pertinence
        """
        parties = []

        # Titre (poids fort)
        if annonce.get('titre'):
            parties.append(annonce['titre'])
            parties.append(annonce['titre'])  # répété pour augmenter le poids

        # Description
        if annonce.get('description'):
            parties.append(annonce['description'])

        # Catégorie
        if annonce.get('categorie'):
            parties.append(annonce['categorie'])

        # Ville et quartier
        if annonce.get('ville'):
            parties.append(annonce['ville'])
        if annonce.get('quartier'):
            parties.append(annonce['quartier'])

        # Caractéristiques
        if annonce.get('meuble'):
            parties.append('meublé meuble')
        if annonce.get('nb_chambres'):
            parties.append(f"{annonce['nb_chambres']} chambre chambres")
        if annonce.get('type_transaction'):
            parties.append(annonce['type_transaction'])

        return self._nettoyer_texte(' '.join(parties))

    def entrainer_sur_corpus(self, annonces: list):
        """Entraîne le vectoriseur sur toutes les annonces"""
        if not annonces:
            return

        self.corpus = [self._enrichir_texte(a) for a in annonces]
        self.corpus_annonces = annonces

        # Entraîner le vectoriseur
        textes_valides = [t for t in self.corpus if t.strip()]
        if textes_valides:
            self.vectorizer.fit(textes_valides)
            self.est_entraine = True

    def calculer_similarite(self, texte_requete: str,
                             annonces: list) -> list:
        """
        Compare la requête avec toutes les annonces
        Retourne un score entre 0 et 1
        """
        if not texte_requete or not annonces:
            return [0.5] * len(annonces)

        texte_requete = self._nettoyer_texte(texte_requete)

        if not texte_requete:
            return [0.5] * len(annonces)

        # Textes des annonces
        textes_annonces = [
            self._enrichir_texte(a) for a in annonces
        ]

        try:
            # Vectoriser tous les textes ensemble
            tous_textes = [texte_requete] + textes_annonces
            tous_textes = [t if t.strip() else 'vide' for t in tous_textes]

            # Entraîner si pas encore fait
            if not self.est_entraine:
                self.vectorizer.fit(tous_textes)

            matrice = self.vectorizer.transform(tous_textes)

            # Similarité cosinus entre la requête et chaque annonce
            vecteur_requete  = matrice[0]
            vecteurs_annonces = matrice[1:]

            scores = cosine_similarity(
                vecteur_requete,
                vecteurs_annonces
            )[0]

            return [float(s) for s in scores]

        except Exception as e:
            print(f"Erreur NLP: {e}")
            return [0.5] * len(annonces)

    def analyser_qualite_description(self, description: str) -> float:
        """
        Score de qualité d'une description d'annonce
        """
        if not description:
            return 0.0

        description = description.lower()

        # Score longueur (max à 500 caractères)
        score_longueur = min(len(description) / 500, 1.0)

        # Score nombre de mots
        nb_mots       = len(description.split())
        score_mots    = min(nb_mots / 80, 1.0)

        # Mots clés positifs pour une annonce immobilière
        mots_positifs = [
            'lumineux', 'calme', 'moderne', 'rénové', 'parking',
            'sécurisé', 'climatisé', 'gardien', 'piscine', 'terrasse',
            'balcon', 'jardin', 'cuisine équipée', 'proche', 'meublé',
            'standing', 'spacieux', 'confortable', 'neuf', 'sécurité',
            'gardien', 'eau', 'électricité', 'internet', 'wifi',
        ]

        nb_positifs  = sum(
            1 for mot in mots_positifs
            if mot in description
        )
        score_positifs = min(nb_positifs / 6, 1.0)

        # Score ponctuation et structure
        a_ponctuation  = '.' in description or ',' in description
        score_structure = 0.3 if a_ponctuation else 0.0

        # Score final pondéré
        score = (
            score_longueur  * 0.30 +
            score_mots      * 0.25 +
            score_positifs  * 0.35 +
            score_structure * 0.10
        )

        return round(min(score, 1.0), 4)

    def extraire_mots_cles(self, texte: str, n: int = 5) -> list:
        """
        Extrait les mots-clés les plus importants d'un texte
        """
        if not texte:
            return []

        texte_nettoye = self._nettoyer_texte(texte)

        try:
            # Vectoriser le texte
            vecteur = self.vectorizer.transform([texte_nettoye])
            feature_names = self.vectorizer.get_feature_names_out()
            scores = vecteur.toarray()[0]

            # Trier par score décroissant
            indices = scores.argsort()[::-1][:n]
            mots_cles = [
                feature_names[i]
                for i in indices
                if scores[i] > 0
            ]
            return mots_cles
        except Exception:
            return []
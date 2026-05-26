import numpy as np
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.preprocessing import LabelEncoder
from sklearn.model_selection import train_test_split

class PrixPredictor:
    def __init__(self):
        self.modele          = RandomForestRegressor(
            n_estimators=100,
            random_state=42
        )
        self.encodeur_ville  = LabelEncoder()
        self.encodeur_cat    = LabelEncoder()
        self.encodeur_trans  = LabelEncoder()
        self.est_entraine    = False

    def preparer_features(self, annonces: list) -> pd.DataFrame:
        """Prépare les features pour l'entraînement"""
        df = pd.DataFrame(annonces)

        # Remplir les valeurs manquantes
        df['surface_m2']   = df['surface_m2'].fillna(df['surface_m2'].median() if len(df) > 0 else 50)
        df['nb_chambres']  = df['nb_chambres'].fillna(1)
        df['meuble']       = df['meuble'].fillna(False).astype(int)

        return df

    def entrainer(self, annonces: list):
        """Entraîne le modèle sur les annonces existantes"""
        if len(annonces) < 10:
            print("⚠️ Pas assez de données pour entraîner le modèle prix.")
            return

        df = self.preparer_features(annonces)

        # Encoder les variables catégorielles
        df['ville_enc'] = self.encodeur_ville.fit_transform(
            df['ville'].fillna('Inconnue')
        )
        df['cat_enc']   = self.encodeur_cat.fit_transform(
            df['categorie'].fillna('appartement')
        )
        df['trans_enc'] = self.encodeur_trans.fit_transform(
            df['type_transaction'].fillna('location')
        )

        # Features et cible
        features = ['surface_m2', 'nb_chambres', 'meuble',
                    'ville_enc', 'cat_enc', 'trans_enc']
        X = df[features].values
        y = df['prix'].values

        # Entraînement
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, random_state=42
        )
        self.modele.fit(X_train, y_train)
        self.est_entraine = True

        score = self.modele.score(X_test, y_test)
        print(f" Modèle prix entraîné - Score R²: {score:.2f}")

    def predire_prix(self, ville: str, categorie: str, surface_m2: float,
                     nb_chambres: int, meuble: bool,
                     type_transaction: str) -> dict:
        """Prédit le prix d'un bien selon ses caractéristiques"""

        if not self.est_entraine:
            return {
                "prix_estime"   : None,
                "fourchette_min": None,
                "fourchette_max": None,
                "fiabilite"     : "indisponible",
            }

        try:
            # Encoder les valeurs
            ville_enc = self.encodeur_ville.transform([ville])[0] \
                if ville in self.encodeur_ville.classes_ else 0
            cat_enc   = self.encodeur_cat.transform([categorie])[0] \
                if categorie in self.encodeur_cat.classes_ else 0
            trans_enc = self.encodeur_trans.transform([type_transaction])[0] \
                if type_transaction in self.encodeur_trans.classes_ else 0

            X = np.array([[surface_m2, nb_chambres, int(meuble),
                           ville_enc, cat_enc, trans_enc]])

            prix_predit = self.modele.predict(X)[0]

            return {
                "prix_estime"   : round(float(prix_predit), 2),
                "fourchette_min": round(float(prix_predit * 0.85), 2),
                "fourchette_max": round(float(prix_predit * 1.15), 2),
                "fiabilite"     : "haute" if self.est_entraine else "basse",
            }
        except Exception as e:
            print(f"Erreur prédiction prix: {e}")
            return {
                "prix_estime"   : None,
                "fourchette_min": None,
                "fourchette_max": None,
                "fiabilite"     : "erreur",
            }
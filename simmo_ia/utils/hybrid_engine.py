# moteur_hybride.py
from models.recommandation import MoteurRecommandation
from utils.photo_pipeline  import appliquer_meilleure_photo
from models.photo_selector import PhotoSelector


class MoteurHybride(MoteurRecommandation):
    """
    Hérite de MoteurRecommandation.
    Surcharge uniquement recommander() pour ajouter
    la sélection de photo avant le scoring.
    """
    def recommander(self, annonces, requete):
        # 1. Appliquer la meilleure photo sur chaque annonce
        annonces = appliquer_meilleure_photo(annonces)

        # 2. Déléguer tout le reste au moteur parent
        return super().recommander(annonces, requete)

    def appliquer_meilleure_photo(annonces):

        selector = PhotoSelector()

        for annonce in annonces:

            photos = annonce.get("photos", [])

            annonce["photo"] = selector.choisir_meilleure(photos)

        return annonces
    
    def _calculer_score_prix(self, annonces, requete):
        return self._score_prix(annonces, requete)

    def _calculer_score_popularite(self, annonces):
        return self._score_popularite(annonces)
    
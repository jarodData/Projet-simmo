import requests
from geopy.geocoders import Nominatim
from geopy.distance import geodesic
import time
from functools import lru_cache

class ScoringContextuel:
    def __init__(self):
        # 1. USER_AGENT OBLIGATOIRE sinon Nominatim = 403
        self.geolocator = Nominatim(user_agent="SIMMo-App/1.0")
        self.overpass_url = "https://overpass-api.de/api/interpreter"
        # 2. Cache simple pour éviter de spammer l'API
        self._cache_geocode = {}
        print("ScoringContextuel initialisé avec Nominatim et Overpass")
    @lru_cache(maxsize=100)
    
    def geocoder_lieu(self, nom, ville):
        """Convertit 'Université de Yaoundé I, Yaoundé' → lat, lon"""
        query = f"{nom}, {ville}, Cameroun"

        # Cache local
        if query in self._cache_geocode:
            return self._cache_geocode[query]

        try:
            location = self.geolocator.geocode(query, timeout=10)
            if location:
                result = {
                    'nom': location.address,
                    'latitude': location.latitude,
                    'longitude': location.longitude
                }
                self._cache_geocode[query] = result
                time.sleep(1) # Respect rate limit Nominatim: 1 req/sec
                return result
        except Exception as e:
            print(f"Erreur geocodage {query}: {e}")

        return None
    

    def chercher_lieux_proches(self, lat, lon, type_lieu, religion=None, rayon_km=2):
        """Overpass API pour trouver mosquées, écoles, hôpitaux..."""
        # 3. TIMEOUT 10s sinon ça freeze
        query = f"""
        [out:json][timeout:10];
        (
          node["{type_lieu}"](around:{rayon_km*1000},{lat},{lon});
          way["{type_lieu}"](around:{rayon_km*1000},{lat},{lon});
        );
        out center 20;
        """

        if religion:
            query = query.replace(f'["{type_lieu}"]', f'["{type_lieu}"]["religion"="{religion}"]')

        try:
            r = requests.post(self.overpass_url, data=query, timeout=10)
            r.raise_for_status()
            data = r.json()

            lieux = []
            for el in data.get('elements', []):
                lat2 = el.get('lat') or el.get('center', {}).get('lat')
                lon2 = el.get('lon') or el.get('center', {}).get('lon')
                if lat2 and lon2:
                    dist = geodesic((lat, lon), (lat2, lon2)).km
                    lieux.append({
                        'nom': el.get('tags', {}).get('name', 'Sans nom'),
                        'distance_km': round(dist, 2),
                        'lat': lat2,
                        'lon': lon2
                    })
            return sorted(lieux, key=lambda x: x['distance_km'])
        except Exception as e:
            print(f"Erreur Overpass: {e}")
            return []

    def scorer_annonces_par_contexte(self, annonces, lat_travail, lon_travail, rayon_km=2):
        """
        Modifie directement les annonces pour ajouter prix_moyen_marche
        Retourne une liste de floats [0.95, 0.3, 0.7,...]
        """
        scores_proximite = []  #
    
    # 1. Calcule le prix moyen du marché 1 seule fois = montant vert
        prix_moyen_marche = self.calculer_prix_moyen_marche('Chambre', 'Douala')

        for annonce in annonces:
        # 2. Ajoute le prix moyen à chaque annonce
            annonce['prix_moyen_marche'] = prix_moyen_marche

        # 3. Calcule score distance
        if 'latitude' in annonce and 'longitude' in annonce and annonce['latitude'] and annonce['longitude']:
            try:
                dist = geodesic((lat_travail, lon_travail),
                               (annonce['latitude'], annonce['longitude'])).km

                # Score: 1.0 si 0km, 0.0 si >=10km
                score = max(0, 1 - dist/10)
                annonce['distance_km'] = round(dist, 2)
            except:
                score = 0.5
                annonce['distance_km'] = None
        else:
            score = 0.5
            annonce['distance_km'] = None

        scores_proximite.append(round(score, 2)) # ← juste le float

        return scores_proximite
        # 7. Trie par meilleur score
        # return sorted(annonces_scored, key=lambda x: x['score_contextuel'], reverse=True)
    def calculer_prix_moyen_marche(self, type_bien, ville, quartier=None):
        try:
            prix_base = {
                'Chambre': 65000,
                'Studio': 120000,
                'Appartement': 200000,
                'Villa': 450000
            }
            prix_base_val = prix_base.get(type_bien, 80000)

            if quartier:
                quartier_lower = quartier.lower()
                if 'bonapriso' in quartier_lower or 'bonanjo' in quartier_lower:
                    prix_base_val *= 1.4  # +40% quartiers chics
                elif 'bepanda' in quartier_lower or 'makepe' in quartier_lower:
                    prix_base_val *= 1.1  # +10% quartiers moyens
                elif 'nyalla' in quartier_lower or 'pk14' in quartier_lower:
                    prix_base_val *= 0.85  # -15% quartiers populaires

            return round(prix_base_val)
        except Exception:
            return 80000  # prix par défaut si crash
    def analyser_contexte_quartier(self, lat, lon, rayon_km=2):
        compteur = {'lieux_culte': 0, 'mosquees': 0, 'eglises': 0,
                    'ecoles': 0, 'hopitaux': 0, 'pharmacies': 0,
                    'marches': 0, 'banques': 0}

        mosquees = self.cher_lieux_proches(lat, lon, 'place_of_worship', 'muslim', rayon_km)
        compteur['mosquees'] = len(mosquees)
        compteur['lieux_culte'] += len(mosquees)

        eglises = self.cher_lieux_proches(lat, lon, 'place_of_worship', 'christian', rayon_km)
        compteur['eglises'] = len(eglises)
        compteur['lieux_culte'] += len(eglises)

        compteur['ecoles'] = len(self.cher_lieux_proches(lat, lon, 'amenity', 'school', rayon_km))
        compteur['hopitaux'] = len(self.cher_lieux_proches(lat, lon, 'amenity', 'hospital', rayon_km))
        compteur['pharmacies'] = len(self.cher_lieux_proches(lat, lon, 'amenity', 'pharmacy', rayon_km))
        compteur['marches'] = len(self.cher_lieux_proches(lat, lon, 'amenity', 'marketplace', rayon_km))
        compteur['banques'] = len(self.cher_lieux_proches(lat, lon, 'amenity', 'bank', rayon_km))

        total = sum(compteur.values())
        compteur['score_services'] = round(min(1.0, total / 20), 2)
        return compteur
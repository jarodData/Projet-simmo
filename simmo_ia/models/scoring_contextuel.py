import requests
import math
from typing import Optional

class ScoringContextuel:

    # ── Lieux de culte reconnus ──────────────
    LIEUX_CULTE = {
        # Musulman
        'musulman'  : ['mosque', 'place_of_worship'],
        'islam'     : ['mosque', 'place_of_worship'],
        'mosquee'   : ['mosque'],

        # Chrétien catholique
        'catholique': ['place_of_worship'],
        'catholic'  : ['place_of_worship'],

        # Chrétien protestant
        'protestant': ['place_of_worship'],
        'chretien'  : ['place_of_worship'],
        'eglise'    : ['place_of_worship'],

        # Loisirs & commodités
        'marche'    : ['marketplace', 'market'],
        'hopital'   : ['hospital', 'clinic'],
        'pharmacie' : ['pharmacy'],
        'ecole'     : ['school', 'university', 'college'],
        'universite': ['university', 'college'],
        'stade'     : ['stadium', 'sports_centre'],
        'supermarche': ['supermarket', 'mall'],
        'restaurant': ['restaurant', 'fast_food'],
        'banque'    : ['bank', 'atm'],
    }

    # ── Religion → tag OSM ───────────────────
    RELIGION_TAGS = {
        'musulman'  : 'muslim',
        'islam'     : 'muslim',
        'catholique': 'christian',
        'protestant': 'christian',
        'chretien'  : 'christian',
    }

    def geocoder_lieu(self, nom_lieu: str, ville: str = '') -> Optional[dict]:
        """
        Géocode un lieu via Nominatim (OpenStreetMap)
        Retourne les coordonnées GPS
        """
        try:
            query = f"{nom_lieu}, {ville}, Cameroun" if ville else f"{nom_lieu}, Cameroun"
            url   = "https://nominatim.openstreetmap.org/search"
            params = {
                'q'              : query,
                'format'         : 'json',
                'limit'          : 1,
                'addressdetails' : 1,
            }
            headers = {'User-Agent': 'SIMMo-App/1.0'}
            response = requests.get(url, params=params, headers=headers, timeout=5)
            data     = response.json()

            if data:
                return {
                    'nom'      : data[0].get('display_name', nom_lieu),
                    'latitude' : float(data[0]['lat']),
                    'longitude': float(data[0]['lon']),
                    'type'     : data[0].get('type', ''),
                }
            return None
        except Exception as e:
            print(f"Erreur géocodage: {e}")
            return None

    def chercher_lieux_proches(self, lat: float, lon: float,
                                type_lieu: str, religion: str = None,
                                rayon_km: float = 3) -> list:
        """
        Cherche des lieux via Overpass API (OpenStreetMap)
        autour de coordonnées GPS
        """
        try:
            rayon_m = rayon_km * 1000

            # Construction de la requête Overpass
            if religion and religion.lower() in self.RELIGION_TAGS:
                tag_religion = self.RELIGION_TAGS[religion.lower()]
                query = f"""
                [out:json][timeout:10];
                (
                    node["amenity"="place_of_worship"]
                       ["religion"="{tag_religion}"]
                       (around:{rayon_m},{lat},{lon});
                    way["amenity"="place_of_worship"]
                       ["religion"="{tag_religion}"]
                       (around:{rayon_m},{lat},{lon});
                );
                out center;
                """
            else:
                amenities = self.LIEUX_CULTE.get(
                    type_lieu.lower(), [type_lieu.lower()]
                )
                amenity_filters = ''.join([
                    f'node["amenity"="{a}"](around:{rayon_m},{lat},{lon});'
                    for a in amenities
                ])
                query = f"""
                [out:json][timeout:10];
                ({amenity_filters});
                out center;
                """

            response = requests.post(
                'https://overpass-api.de/api/interpreter',
                data=query,
                timeout=15
            )
            data = response.json()

            lieux = []
            for element in data.get('elements', [])[:10]:
                lat_lieu = element.get('lat') or element.get('center', {}).get('lat')
                lon_lieu = element.get('lon') or element.get('center', {}).get('lon')
                tags     = element.get('tags', {})

                if lat_lieu and lon_lieu:
                    distance = self._haversine(lat, lon, lat_lieu, lon_lieu)
                    lieux.append({
                        'nom'      : tags.get('name', type_lieu),
                        'latitude' : lat_lieu,
                        'longitude': lon_lieu,
                        'distance_km': round(distance, 2),
                        'type'     : tags.get('amenity', type_lieu),
                    })

            # Trier par distance
            lieux.sort(key=lambda x: x['distance_km'])
            return lieux

        except Exception as e:
            print(f"Erreur Overpass: {e}")
            return []

    def scorer_annonces_par_contexte(self, annonces: list,
                                      lat_ref: float, lon_ref: float,
                                      rayon_max_km: float = 5) -> list:
        """
        Score chaque annonce selon sa proximité
        avec le point de référence (lieu de travail, école, etc.)
        """
        scores = []
        for annonce in annonces:
            lat_a = annonce.get('latitude')
            lon_a = annonce.get('longitude')

            if not lat_a or not lon_a:
                scores.append(0.3)
                continue

            distance = self._haversine(
                lat_ref, lon_ref,
                float(lat_a), float(lon_a)
            )

            # Score décroissant selon la distance
            if distance <= 0.5:
                score = 1.0
            elif distance <= 1:
                score = 0.9
            elif distance <= 2:
                score = 0.75
            elif distance <= 3:
                score = 0.6
            elif distance <= 5:
                score = 0.4
            elif distance <= 10:
                score = 0.2
            else:
                score = 0.0

            scores.append(round(score, 4))

        return scores

    def analyser_contexte_quartier(self, lat: float, lon: float) -> dict:
        """
        Analyse l'environnement d'un quartier :
        nombre de mosquées, églises, écoles, hôpitaux, marchés à proximité
        """
        try:
            query = f"""
            [out:json][timeout:15];
            (
                node["amenity"~"mosque|place_of_worship|school|university|
                     hospital|clinic|pharmacy|marketplace|supermarket|bank"]
                    (around:2000,{lat},{lon});
            );
            out;
            """
            response = requests.post(
                'https://overpass-api.de/api/interpreter',
                data=query,
                timeout=20
            )
            data     = response.json()
            elements = data.get('elements', [])

            analyse = {
                'lieux_culte'  : 0,
                'mosquees'     : 0,
                'eglises'      : 0,
                'ecoles'       : 0,
                'hopitaux'     : 0,
                'pharmacies'   : 0,
                'marches'      : 0,
                'banques'      : 0,
                'score_services': 0.0,
            }

            for e in elements:
                tags    = e.get('tags', {})
                amenity = tags.get('amenity', '')
                religion = tags.get('religion', '')

                if amenity == 'place_of_worship':
                    analyse['lieux_culte'] += 1
                    if religion == 'muslim':
                        analyse['mosquees'] += 1
                    elif religion == 'christian':
                        analyse['eglises'] += 1
                elif amenity in ['school', 'university', 'college']:
                    analyse['ecoles'] += 1
                elif amenity in ['hospital', 'clinic']:
                    analyse['hopitaux'] += 1
                elif amenity == 'pharmacy':
                    analyse['pharmacies'] += 1
                elif amenity in ['marketplace', 'supermarket']:
                    analyse['marches'] += 1
                elif amenity == 'bank':
                    analyse['banques'] += 1

            # Score global des services du quartier
            score = min(
                (analyse['ecoles']    * 0.2 +
                 analyse['hopitaux']  * 0.25 +
                 analyse['pharmacies']* 0.15 +
                 analyse['marches']   * 0.2 +
                 analyse['banques']   * 0.1 +
                 analyse['lieux_culte'] * 0.1) / 3,
                1.0
            )
            analyse['score_services'] = round(score, 4)

            return analyse

        except Exception as e:
            print(f"Erreur analyse quartier: {e}")
            return {}

    @staticmethod
    def _haversine(lat1, lon1, lat2, lon2) -> float:
        R = 6371
        lat1, lon1 = math.radians(lat1), math.radians(lon1)
        lat2, lon2 = math.radians(lat2), math.radians(lon2)
        dlat = lat2 - lat1
        dlon = lon2 - lon1
        a = math.sin(dlat/2)**2 + math.cos(lat1)*math.cos(lat2)*math.sin(dlon/2)**2
        return R * 2 * math.asin(math.sqrt(a))
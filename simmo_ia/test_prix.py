# test_prix.py
from models.prix_predictor import PrixPredictor
from database import Session, fetch_annonces

print("🔄 Test prédicteur de prix...")

db       = Session()
annonces = fetch_annonces(db)
db.close()

predictor = PrixPredictor()

if len(annonces) < 5:
    print(f"⚠️  Seulement {len(annonces)} annonce(s) en BD")
    print("   → Le modèle nécessite au moins 10 annonces")
    print("   → Test avec données fictives...")

    # Données fictives pour tester
    annonces_fictives = []
    villes      = ['Yaoundé', 'Douala', 'Bafoussam']
    categories  = ['studio', 'appartement', 'villa', 'chambre']
    transactions = ['location', 'vente']

    import random
    random.seed(42)

    for i in range(30):
        ville    = random.choice(villes)
        cat      = random.choice(categories)
        trans    = random.choice(transactions)
        surface  = random.randint(20, 200)
        chambres = random.randint(1, 5)
        meuble   = random.choice([True, False])

        # Prix réaliste selon les caractéristiques
        base = 50000 if trans == 'location' else 5000000
        prix = base + (surface * 1000) + (chambres * 20000)
        if meuble: prix *= 1.2
        if ville == 'Douala': prix *= 1.1

        annonces_fictives.append({
            'id'             : i + 1,
            'titre'          : f"Annonce test {i+1}",
            'description'    : f"Description {i+1}",
            'prix'           : round(prix),
            'surface_m2'     : surface,
            'nb_chambres'    : chambres,
            'meuble'         : meuble,
            'ville'          : ville,
            'categorie'      : cat,
            'type_transaction': trans,
            'vues'           : random.randint(0, 100),
            'latitude'       : None,
            'longitude'      : None,
        })

    annonces = annonces_fictives
    print(f"   → {len(annonces)} annonces fictives générées")

# Entraîner le modèle
print("\n🔄 Entraînement du modèle...")
predictor.entrainer(annonces)

if not predictor.est_entraine:
    print("❌ Modèle non entraîné")
else:
    print("✅ Modèle entraîné !")

    # ── Test de prédictions ──────────────────
    print("\n📊 Tests de prédiction de prix :\n")

    cas_test = [
        {
            'desc'           : 'Studio meublé à Yaoundé (30m², 1 ch)',
            'ville'          : 'Yaoundé',
            'categorie'      : 'studio',
            'surface_m2'     : 30,
            'nb_chambres'    : 1,
            'meuble'         : True,
            'type_transaction': 'location',
        },
        {
            'desc'           : 'Appartement à Douala (80m², 3 ch)',
            'ville'          : 'Douala',
            'categorie'      : 'appartement',
            'surface_m2'     : 80,
            'nb_chambres'    : 3,
            'meuble'         : False,
            'type_transaction': 'location',
        },
        {
            'desc'           : 'Villa à Yaoundé (200m², 5 ch)',
            'ville'          : 'Yaoundé',
            'categorie'      : 'villa',
            'surface_m2'     : 200,
            'nb_chambres'    : 5,
            'meuble'         : True,
            'type_transaction': 'vente',
        },
        {
            'desc'           : 'Chambre à Bafoussam (15m², 1 ch)',
            'ville'          : 'Bafoussam',
            'categorie'      : 'chambre',
            'surface_m2'     : 15,
            'nb_chambres'    : 1,
            'meuble'         : False,
            'type_transaction': 'location',
        },
    ]

    for cas in cas_test:
        result = predictor.predire_prix(
            ville            = cas['ville'],
            categorie        = cas['categorie'],
            surface_m2       = cas['surface_m2'],
            nb_chambres      = cas['nb_chambres'],
            meuble           = cas['meuble'],
            type_transaction = cas['type_transaction'],
        )
        print(f"  📍 {cas['desc']}")
        if result['prix_estime']:
            print(f"     Prix estimé  : {result['prix_estime']:,.0f} F CFA")
            print(f"     Fourchette   : {result['fourchette_min']:,.0f} — {result['fourchette_max']:,.0f} F CFA")
            print(f"     Fiabilité    : {result['fiabilite']}")
        else:
            print(f"     ⚠️  Estimation indisponible")
        print()

print("✅ Tests prix terminés !")
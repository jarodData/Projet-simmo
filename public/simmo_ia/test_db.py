# test_db.py
from database import engine, Session, fetch_annonces
from sqlalchemy import text

print("🔄 Test connexion MySQL...")

try:
    # Test connexion
    with engine.connect() as conn:
        result = conn.execute(text("SELECT 1"))
        print("✅ Connexion MySQL réussie !")

    # Test récupération annonces
    db       = Session()
    annonces = fetch_annonces(db)
    db.close()

    print(f"✅ {len(annonces)} annonce(s) trouvée(s) dans la BD")

    if annonces:
        print("\n📋 Exemple d'annonce :")
        a = annonces[0]
        print(f"   ID       : {a['id']}")
        print(f"   Titre    : {a['titre']}")
        print(f"   Prix     : {a['prix']}")
        print(f"   Ville    : {a['ville']}")
        print(f"   Catégorie: {a['categorie']}")
    else:
        print("⚠️  Aucune annonce active dans la BD")
        print("    → Créez d'abord des annonces via le frontend")

except Exception as e:
    print(f"❌ Erreur connexion : {e}")
    print("   → Vérifiez votre fichier .env")
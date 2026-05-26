from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker
from config import settings

DATABASE_URL =(
    f"mysql+pymysql://{settings.DB_USER}:{settings.DB_PASSWORD}"
    f"@{settings.DB_HOST}:{settings.DB_PORT}/{settings.DB_NAME}"

)

engine  = create_engine(DATABASE_URL)
Session = sessionmaker(bind=engine)

def get_db():
    db = Session()
    try:
        yield db
    finally:
        db.close()
def fetch_annonces(db):
    query = text("""
        SELECT
            a.id,
            a.titre,
            a.description,
            a.prix,
            a.surface_m2,
            a.nb_chambres,
            a.nb_pieces,
            a.type_transaction,
            a.meuble,
            a.vues,
            a.score_ia,
            c.libelle       AS categorie,
            l.ville,
            l.quartier,
            l.latitude,
            l.longitude,
           MAX(p.chemin_image) AS photo
        FROM annonces a
        JOIN categories_bien c ON a.id_categorie = c.id
        JOIN localisations   l ON a.id_localisation = l.id
        LEFT JOIN photos_annonces p
            ON p.id_annonce     = a.id
            AND p.est_principale = 1
        WHERE a.statut = 'active'
        GROUP BY
        a.id, a.titre, a.description, a.prix,
        a.surface_m2, a.nb_chambres, a.nb_pieces,
        a.type_transaction, a.meuble, a.vues,
        a.score_ia, c.libelle,
        l.ville, l.quartier, l.latitude, l.longitude
    """)

    result   = db.execute(query)
    colonnes = list(result.keys())
    rows     = []

    for row in result.fetchall():
        d = dict(zip(colonnes, row))
        #  Convertir None Python en null JSON proprement
        for cle, val in d.items():
            if val is None:
                d[cle] = None
        rows.append(d)
        


        # ✅ LOG ICI
    if rows:
        print("=== PREMIÈRE ANNONCE ===")
        print("ID:", rows[0].get('id'))
        print("TITRE:", rows[0].get('titre'))
        print("PHOTO:", rows[0].get('photo'))
        print("TOUTES LES CLÉS:", list(rows[0].keys()))
                # Dans fetch_annonces() ou hybrid_engine.py

        return rows
def fetch_historique_user(db, user_id: int):
    """Récupère l'historique de recherche d'un utilisateur"""
    query = text("""
        SELECT terme_recherche, filtres_appliques, created_at
        FROM historique_recherches
        WHERE id_user = :user_id
        ORDER BY created_at DESC
        LIMIT 20
    """)
    result = db.execute(query, {"user_id": user_id})
    colonnes = result.keys()
    return [dict(zip(colonnes, row)) for row in result.fetchall()]









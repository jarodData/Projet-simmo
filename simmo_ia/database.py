from sqlalchemy import create_engine, text
from sqlalchemy.orm import sessionmaker
from config import settings

DATABASE_URL = (
    "mysql+pymysql://{user}:{pwd}@{host}:{port}/{db}"
).format(
    user = settings.DB_USER,
    pwd  = settings.DB_PASSWORD,
    host = settings.DB_HOST,
    port = settings.DB_PORT,
    db   = settings.DB_NAME,
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

            c.libelle AS categorie,
            l.ville,
            l.quartier,
            l.latitude,
            l.longitude

        FROM annonces a
        INNER JOIN categories_bien c ON a.id_categorie = c.id
        INNER JOIN localisations l ON a.id_localisation = l.id
        WHERE a.statut = 'active'
        ORDER BY a.created_at DESC
    """)

    result = db.execute(query)
    columns = result.keys()

    annonces = []

    for row in result.fetchall():
        annonces.append(dict(zip(columns, row)))

    # 🔥 récupérer photos
    query_photos = text("""
        SELECT id_annonce, chemin_image, est_principale
        FROM photos_annonces
    """)

    result_photos = db.execute(query_photos)

    photos_dict = {}

    for row in result_photos.fetchall():

        id_annonce = row[0]

        photo = {
            "chemin_image": row[1],
            "est_principale": bool(row[2])
        }

        photos_dict.setdefault(id_annonce, []).append(photo)

    # 🔥 injecter photos
    for annonce in annonces:
        annonce["photos"] = photos_dict.get(annonce["id"], [])

    return annonces

def fetch_historique_user(db, user_id):
    """Recupere l'historique de recherche d'un utilisateur"""
    query = text("""
        SELECT
            terme_recherche,
            filtres_appliques,
            created_at
        FROM historique_recherches
        WHERE id_user = :user_id
        ORDER BY created_at DESC
        LIMIT 20
    """)
    result   = db.execute(query, {"user_id": user_id})
    colonnes = list(result.keys())
    return [
        dict(zip(colonnes, row))
        for row in result.fetchall()
    ]
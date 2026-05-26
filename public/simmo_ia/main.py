from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from contextlib import asynccontextmanager
from routes.ia_routes import router
from utils.hybrid_engine import MoteurHybride
from database import Session, fetch_annonces
import routes.ia_routes as ia_routes

@asynccontextmanager
async def lifespan(app: FastAPI):
    print("Démarrage de SIMMo IA...")
    moteur   = MoteurHybride()
    db       = Session()
    annonces = fetch_annonces(db)
    db.close()

    if annonces:
        moteur.entrainer(annonces)
    else:
        print("Aucune annonce trouvée. Modèles non entrainés.")

    ia_routes.moteur = moteur
    print("SIMMo IA prête.")
    yield
    print("Arrêt de SIMMo IA.")

app = FastAPI(
    title       = "SIMMo IA API",
    description = "API Intelligence Artificielle pour SIMMo",
    version     = "1.0.0",
    lifespan    = lifespan
)

#  CORS — Autoriser les requêtes depuis le frontend
app.add_middleware(
    CORSMiddleware,
    allow_origins     = ["*"],
    allow_credentials = True,
    allow_methods     = ["*"],
    allow_headers     = ["*"],
)

app.include_router(router, prefix="/api/ia")
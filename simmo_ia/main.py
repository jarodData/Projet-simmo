from fastapi                 import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from contextlib              import asynccontextmanager
from routes.ia_routes        import router as ia_router, moteur, recharger_moteur
import logging
from routes.ia_routes import recharger_moteur, demarrer_scheduler

logging.basicConfig(level=logging.INFO)

@asynccontextmanager
async def lifespan(app: FastAPI):
    print("Démarrage SIMMo IA...")
    # Entraîner le moteur au démarrage
   # 1. Premier entraînement
    await recharger_moteur()
    print(f"Moteur prêt — {len(moteur.prix.annonces)} annonces chargées.")
    # 2. Démarrer le scheduler
    demarrer_scheduler()

    yield
    
    # 3. Arrêter le scheduler proprement
    from routes.ia_routes import scheduler
    if scheduler.running:
        scheduler.shutdown(wait=False)
    print("Arrêt SIMMo IA.")

app = FastAPI(
    title       = "SIMMo IA API",
    description = "API Intelligence Artificielle pour SIMMo",
    version     = "1.0.0",
    lifespan    = lifespan,
)

from fastapi import APIRouter, HTTPException, Header
from pydantic import BaseModel
import pandas as pd
import joblib
import logging

router = APIRouter()
model = joblib.load('C:\www\projet-simmo\simmo_ia\simmo_ia\modeles\modele_prix.pkl')

class PayloadModel(BaseModel):
    ville: str
    categorie: str
    surface_m2: float
    nb_chambres: int
    meuble: bool
    type_transaction: str
    quartier: str

@router.post('/api/ia/predire-prix')
async def predire_prix(payload: PayloadModel, x_api_key: str = Header(None)):
    print(f"DEBUG FastAPI: {payload.dict()}") # <-- regarde ton terminal
    
    features = [[
        payload.ville, 
        payload.categorie, 
        payload.surface_m2,
        payload.nb_chambres,
        payload.meuble,
        payload.type_transaction,
        payload.quartier
    ]]
    
    df = pd.DataFrame(features, columns=['ville', 'categorie', 'surface_m2', 'nb_chambres', 'meuble', 'type_transaction', 'quartier'])
    prix_estime = model.predict(df)[0]
    
    fourchette_min = max(prix_estime * 0.7, 50000)
    fourchette_max = prix_estime * 1.3
    fiabilite = 'haute' if payload.surface_m2 > 15 else 'basse'
    
    return {
        "prix_estime": round(prix_estime),
        "fourchette_min": round(fourchette_min),
        "fourchette_max": round(fourchette_max),
        "fiabilite": fiabilite
    }
app.add_middleware(
    CORSMiddleware,
    allow_origins     = ["*"],
    allow_credentials = True,
    allow_methods     = ["*"],
    allow_headers     = ["*"],
)

app.include_router(ia_router)

@app.get("/")
def root():
    return {"message": "SIMMo IA opérationnelle", "version": "1.0.0"}
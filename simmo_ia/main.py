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
    # print(f"Moteur prêt — {nb} annonces chargées.")
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
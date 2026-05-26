# test_nlp.py
from models.nlp_analyser import NLPAnalyser

print("🔄 Chargement du modèle NLP...")
nlp = NLPAnalyser()

# ── Test 1 : Similarité ──────────────────────
print("\n📊 Test 1 — Similarité entre textes")

annonces_test = [
    {'titre': 'Studio meublé à Bastos', 'description': 'Beau studio climatisé avec parking sécurisé'},
    {'titre': 'Villa à Biyem-Assi',     'description': 'Grande villa avec jardin et piscine'},
    {'titre': 'Chambre chez habitant',  'description': 'Chambre simple dans résidence calme proche université'},
    {'titre': 'Appartement 3 pièces',   'description': 'Appartement moderne lumineux avec balcon'},
]

requetes_test = [
    "studio meublé proche université",
    "villa avec jardin",
    "logement famille avec plusieurs chambres",
]

for requete in requetes_test:
    scores = nlp.calculer_similarite(requete, annonces_test)
    print(f"\n  Requête : '{requete}'")
    for i, (a, s) in enumerate(zip(annonces_test, scores)):
        barre = '█' * int(s * 20)
        print(f"    [{barre:<20}] {s:.2f} — {a['titre']}")

# ── Test 2 : Qualité description ─────────────
print("\n📊 Test 2 — Qualité des descriptions")

descriptions = [
    "ok",
    "Beau studio",
    "Studio moderne et lumineux situé au quartier Bastos. Entièrement meublé avec cuisine équipée, climatisation et sécurité 24h. Proche de tous commerces.",
    "Villa spacieuse avec jardin, piscine, parking sécurisé, gardien, terrasse, cuisine équipée et climatisation dans tous les pièces.",
]

for desc in descriptions:
    score = nlp.analyser_qualite_description(desc)
    barre = '█' * int(score * 20)
    print(f"  [{barre:<20}] {score:.2f} — '{desc[:50]}...'")

print("\n✅ Tests NLP terminés !")
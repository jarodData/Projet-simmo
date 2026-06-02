<?php
namespace App\Http\Controllers;

use App\Models\Annonce;
use App\Models\CategorieBien;
use App\Models\Localisation;
use App\Models\PhotoAnnonce;
use App\Models\StatistiqueAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnonceController extends Controller
{
    // ============================================
    // ROUTES PUBLIQUES
    // ============================================

    //  Liste publique des annonces
    //  Retourner directement la pagination
public function index(Request $request)
{
    $limit = $request->get('limit', 12);

    $annonces = Annonce::with(
            'localisation',
            'categorie',
            'photoPrincipale'
        )
        ->where('statut', 'active')
        ->orderBy('created_at', 'desc')
        ->paginate($limit);

    return response()->json($annonces);
}

    //  Détail public d'une annonce
    public function show($id)
{
    // Chercher sans filtrer par statut
    $annonce = Annonce::with([
        'localisation',
        'categorie',
        'photos',
        'agent.plan',
    ])->find($id);

    if (!$annonce) {
        return response()->json([
            'message' => 'Annonce introuvable.',
        ], 404);
    }

    // Incrémenter les vues
    $annonce->increment('vues');

    // Annonces similaires
    $similaires = Annonce::with([
        'localisation',
        'categorie',
        'photoPrincipale',
    ])
    ->where('id_categorie', $annonce->id_categorie)
    ->where('statut', 'active')
    ->where('id', '!=', $annonce->id)
    ->limit(3)
    ->get();

    return response()->json([
        'annonce'    => $annonce,
        'similaires' => $similaires,
    ]);
}

    // ============================================
    // ROUTES AGENT (protégées)
    // ============================================

    //  Créer une annonce
    public function store(Request $request)
    {
        $request->validate([
            'titre'            => 'required|string|max:255',
            'description'      => 'required|string',
            'prix'             => 'required|numeric|min:0',
            'surface_m2'       => 'nullable|numeric|min:0',
            'nb_pieces'        => 'nullable|integer|min:1',
            'nb_chambres'      => 'nullable|integer|min:0',
            'nb_salles_bain'   => 'nullable|integer|min:0',
            'type_transaction' => 'required|in:location,vente',
            'meuble'           => 'boolean',
            'id_categorie'     => 'required|exists:categories_bien,id',
            'ville'            => 'required|string|max:100',
            'quartier'         => 'nullable|string|max:150',
            'adresse_complete' => 'nullable|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
            'photos'           => 'nullable|array',
            'photos.*'         => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);


        $agent = auth('agent')->user();

        // Vérifier la limite du plan
        $nbAnnonces  = $agent->annonces()
            ->where('statut', 'active')
            ->count();
        $limitePlan  = $agent->plan
            ? $agent->plan->nb_annonces_max
            : 5;

        if ($nbAnnonces >= $limitePlan) {
            return response()->json([
                'message' => "Limite de {$limitePlan} annonces atteinte. Upgradez votre plan.",
                'code'    => 'LIMITE_PLAN',
            ], 403);
        }

        // Créer la localisation
        $localisation = Localisation::create([
            'pays'             => 'Cameroun',
            'ville'            => $request->ville,
            'quartier'         => $request->quartier,
            'adresse_complete' => $request->adresse_complete,
            'latitude'         => $request->latitude,
            'longitude'        => $request->longitude,
        ]);

        // Créer l'annonce
        $annonce = Annonce::create([
            'titre'            => $request->titre,
            'description'      => $request->description,
            'prix'             => $request->prix,
            'surface_m2'       => $request->surface_m2,
            'nb_pieces'        => $request->nb_pieces,
            'nb_chambres'      => $request->nb_chambres,
            'nb_salles_bain'   => $request->nb_salles_bain,
            'type_transaction' => $request->type_transaction,
            'meuble'           => $request->meuble ?? false,
            'statut'           => 'active',
            'vues'             => 0,
            'id_agent'         => $agent->id,
            'id_categorie'     => $request->id_categorie,
            'id_localisation'  => $localisation->id,
        ]);

        if ($request->hasFile('photos')) {
    $estPremiere = true;

    foreach ($request->file('photos') as $photo) {
        $chemin = $photo->store('annonces/photos', 'public');

        PhotoAnnonce::create([
            'id_annonce'     => $annonce->id,
            'chemin_image'   => $chemin,
            'est_principale' => $estPremiere,
        ]);

        $estPremiere = false;
        

    }
}

        // Mettre à jour les statistiques
        $this->updateStatistique(
            $agent->id,
            'nb_annonces_publiees'
        );
            $this->notifierIA();


        return response()->json([
            'message' => 'Annonce créée avec succès !',
            'annonce' => $annonce->load(
                'localisation', 'categorie', 'photos'
            ),
        ], 201);

    }

    private function notifierIA()
{
    try {
        $client = new \GuzzleHttp\Client(['timeout' => 2]);
        $client->post('http://localhost:8001/api/ia/recharger', [
            'headers' => ['X-API-Key' => config('services.ia.key')],
        ]);
    } catch (\Exception $e) {
        // Silencieux — l'IA se rechargera dans 6h de toute façon
    }
}

    //  Liste des annonces de l'agent
    public function mesAnnonces(Request $request)
    {
        $annonces = auth('agent')->user()
            ->annonces()
            ->with('localisation', 'categorie', 'photoPrincipale')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($annonces);
    }

    //  Page modification — retourne les données de l'annonce
    public function edit($id)
    {
        $annonce = Annonce::with(
                'localisation',
                'categorie',
                'photos'
            )
            ->where('id_agent', auth('agent')->id())
            ->findOrFail($id);

        $categories = CategorieBien::all();

        return response()->json([
            'annonce'    => $annonce,
            'categories' => $categories,
        ]);
    }

    //  Modifier une annonce
    public function update(Request $request, $id)
    {
        $annonce = Annonce::where('id', $id)
            ->where('id_agent', auth('agent')->id())
            ->firstOrFail();

        $request->validate([
            'titre'            => 'sometimes|string|max:255',
            'description'      => 'sometimes|string',
            'prix'             => 'sometimes|numeric|min:0',
            'surface_m2'       => 'nullable|numeric|min:0',
            'nb_pieces'        => 'nullable|integer|min:1',
            'nb_chambres'      => 'nullable|integer|min:0',
            'nb_salles_bain'   => 'nullable|integer|min:0',
            'type_transaction' => 'sometimes|in:location,vente',
            'statut'           => 'sometimes|in:active,inactive,vendu',
            'meuble'           => 'boolean',
            'ville'            => 'sometimes|string|max:100',
            'quartier'         => 'nullable|string|max:150',
            'adresse_complete' => 'nullable|string',
            'latitude'         => 'nullable|numeric',
            'longitude'        => 'nullable|numeric',
        ]);

        // Mettre à jour la localisation
        if ($request->hasAny(['ville', 'quartier', 'adresse_complete',
                               'latitude', 'longitude'])) {
            $annonce->localisation->update([
                'ville'            => $request->ville
                    ?? $annonce->localisation->ville,
                'quartier'         => $request->quartier
                    ?? $annonce->localisation->quartier,
                'adresse_complete' => $request->adresse_complete
                    ?? $annonce->localisation->adresse_complete,
                'latitude'         => $request->latitude
                    ?? $annonce->localisation->latitude,
                'longitude'        => $request->longitude
                    ?? $annonce->localisation->longitude,
            ]);
        }

        // Mettre à jour l'annonce
        $annonce->update($request->only([
            'titre', 'description', 'prix', 'surface_m2',
            'nb_pieces', 'nb_chambres', 'nb_salles_bain',
            'type_transaction', 'statut', 'meuble',
        ]));

        return response()->json([
            'message' => 'Annonce modifiée avec succès !',
            'annonce' => $annonce->load(
                'localisation', 'categorie', 'photos'
            ),
        ]);
    }

    //  Supprimer une annonce
    public function destroy($id)
    {
        $annonce = Annonce::where('id', $id)
            ->where('id_agent', auth('agent')->id())
            ->firstOrFail();

        // Supprimer les photos du storage
        foreach ($annonce->photos as $photo) {
            Storage::disk('public')->delete($photo->chemin_image);
        }

        // Supprimer la localisation associée
        $annonce->localisation()->delete();

        // Supprimer l'annonce (cascade sur photos, contacts, etc.)
        $annonce->delete();

        return response()->json([
            'message' => 'Annonce supprimée avec succès.',
        ]);
    }

    //  Ajouter des photos à une annonce existante
    public function ajouterPhotos(Request $request, $id)
    {
        $annonce = Annonce::where('id', $id)
            ->where('id_agent', auth('agent')->id())
            ->firstOrFail();

        $request->validate([
            'photos'   => 'required|array|min:1',
            'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $photosAjoutees  = [];
        $aPrincipal = $annonce->photos()
            ->where('est_principale', true)
            ->exists();

        foreach ($request->file('photos') as $photo) {
            $chemin = $photo->store('annonces/photos', 'public');
            $nouvelle = PhotoAnnonce::create([
                'id_annonce'     => $annonce->id,
                'chemin_image'   => $chemin,
                'est_principale' => !$aPrincipal,
            ]);
            $photosAjoutees[] = $nouvelle;
            $aPrincipal       = true;
        }

        return response()->json([
            'message' => count($photosAjoutees) . ' photo(s) ajoutée(s).',
            'photos'  => $photosAjoutees,
        ]);
    }

    //  Supprimer une photo
    public function supprimerPhoto($idPhoto)
    {
        $photo = PhotoAnnonce::findOrFail($idPhoto);

        // Vérifier que l'annonce appartient à l'agent
        $annonce = Annonce::where('id', $photo->id_annonce)
            ->where('id_agent', auth('agent')->id())
            ->firstOrFail();

        // Si c'est la photo principale, définir une autre
        if ($photo->est_principale) {
            $autrePhoto = PhotoAnnonce::where('id_annonce', $annonce->id)
                ->where('id', '!=', $photo->id)
                ->first();
            if ($autrePhoto) {
                $autrePhoto->update(['est_principale' => true]);
            }
        }

        Storage::disk('public')->delete($photo->chemin_image);
        $photo->delete();

        return response()->json([
            'message' => 'Photo supprimée avec succès.',
        ]);
    }

    // ============================================
    // HELPER STATISTIQUES
    // ============================================
    private function updateStatistique(int $idAgent, string $colonne): void
    {
        $stat = StatistiqueAgent::firstOrCreate(
            [
                'id_agent' => $idAgent,
                'mois'     => now()->month,
                'annee'    => now()->year,
            ],
            [
                'nb_annonces_publiees' => 0,
                'nb_contacts_recus'    => 0,
                'nb_services_rendus'   => 0,
                'nb_vues_total'        => 0,
            ]
        );
        $stat->increment($colonne);
    }
}
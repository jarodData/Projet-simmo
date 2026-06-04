<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UtilisateurAuthController;
use App\Http\Controllers\Auth\AgentAuthController;
use App\Http\Controllers\AnnonceController;
use App\Http\Controllers\RechercheController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardAgentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\ChattController;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request; // ✅ déjà importé normalement


// ============================================
// ROUTES PUBLIQUES (sans token)
// ============================================
Route::get('/annonces',
    [AnnonceController::class, 'index']);
Route::get('/annonces/{id}',
    [AnnonceController::class, 'show']);
Route::get('/plans',
    [PlanController::class, 'index']);

Route::get('/test-mail', function () {
    Mail::raw('Test SIMMo', function ($m) {
        $m->to('tonmail@gmail.com')->subject('Test');
    });
});

// Route publique pour lister les agents
// ✅ Route directe sans controller
Route::get('/agents', function() {
    $agents = \App\Models\AgentImmobilier::where('statut', 'actif')
        ->withCount('annonces')
        ->with('plan')
        ->select('id', 'nom', 'prenom', 'email', 'telephone', 'statut')
        ->get()
        ->map(function($a) {
            return [
                'id'            => $a->id,
                'nom'           => $a->nom,
                'prenom'        => $a->prenom,
                'email'         => $a->email,
                'telephone'     => $a->telephone,
                'nb_annonces'   => $a->annonces_count,
                'plan'          => $a->plan,
            ];
        });

    return response()->json($agents);
});

// ============================================
// AUTH UTILISATEUR
// ============================================
Route::prefix('auth/utilisateur')->group(function () {
    Route::post('/register',
        [UtilisateurAuthController::class, 'register']);
    Route::post('/login',
        [UtilisateurAuthController::class, 'login']);
    Route::get('/verify/{token}',
        [UtilisateurAuthController::class, 'verify']);
});
Route::get('/auth/utilisateur/verify/{token}', 
    [UtilisateurAuthController::class, 'verify']
);

// ============================================
// AUTH AGENT
// ============================================
Route::prefix('auth/agent')->group(function () {
    Route::post('/register',
        [AgentAuthController::class, 'register']);
    Route::post('/login',
        [AgentAuthController::class, 'login']);
    Route::get('/verify/{token}',
        [AgentAuthController::class, 'verify']);
});
// Recherche (protégée)
        Route::get('/recherche',
            [RechercheController::class, 'rechercher']);

// ============================================
// ROUTES UTILISATEUR CONNECTÉ
// ============================================
Route::middleware('auth:sanctum')
    ->prefix('utilisateur')
    ->group(function () {

        // Auth
        Route::post('/logout',
            [UtilisateurAuthController::class, 'logout']);
        Route::get('/profil',
            [UtilisateurAuthController::class, 'profil']);
        Route::put('/profil',
            [UtilisateurAuthController::class, 'updateProfil']);

        
        Route::get('/historique',
            [RechercheController::class, 'historique']);
        Route::delete('/historique',
            [RechercheController::class, 'supprimerHistorique']);

        // Contact
        Route::post('/contacter',
            [ContactController::class, 'contacterAgent']);
        Route::get('/mes-contacts',
            [ContactController::class, 'mesContactsUtilisateur']);
    });
    // routes/api.php
Route::post('/contact/agent', [ContactController::class, 'envoyerMessage']);

// ============================================
// ROUTES AGENT CONNECTÉ
// ============================================
Route::middleware('auth:sanctum')
    ->prefix('agent')
    ->group(function () {

        // Auth
        Route::post('/logout',
            [AgentAuthController::class, 'logout']);
        Route::get('/profil',
            [AgentAuthController::class, 'profil']);

        // Dashboard
        Route::get('/dashboard',
            [DashboardAgentController::class, 'index']);
        Route::get('/dashboard/utilisateurs',
            [DashboardAgentController::class, 'utilisateursContacts']);

        // Annonces CRUD
        Route::get('/annonces',
            [AnnonceController::class, 'mesAnnonces']);
        Route::post('/annonces',
            [AnnonceController::class, 'store']);
        Route::get('/annonces/{id}',
            [AnnonceController::class, 'edit']);
        Route::put('/annonces/{id}',
            [AnnonceController::class, 'update']);
        Route::delete('/annonces/{id}',
            [AnnonceController::class, 'destroy']);

        // Photos
        Route::post('/annonces/{id}/photos',
            [AnnonceController::class, 'ajouterPhotos']);
        Route::delete('/photos/{id}',
            [AnnonceController::class, 'supprimerPhoto']);

        // Contacts
        Route::get('/contacts',
            [ContactController::class, 'mesContacts']);
        Route::put('/contacts/{id}/lu',
            [ContactController::class, 'marquerLu']);

        // Plans
        Route::get('/mon-plan',
            [PlanController::class, 'monPlan']);
        Route::post('/souscrire',
            [PlanController::class, 'souscrire']);
        Route::post('/renouveler-plan',
            [PlanController::class, 'renouveler']);

        // Paiement
        Route::post('/paiement/initier',
            [PaiementController::class, 'initier']);
        Route::get('/paiement/verifier/{id}',
            [PaiementController::class, 'verifier']);
    });

// Recherche publique accessible à tous
// mais historique seulement si connecté
Route::get('/recherche',
    [RechercheController::class, 'rechercher']);

// Webhooks paiement (publics)
Route::post('/paiement/orange/webhook',
    [PaiementController::class, 'webhookOrange']);
Route::get('/paiement/orange/callback',
    [PaiementController::class, 'callbackOrange']);



    // ============================================
// ROUTES ADMIN
// ============================================
Route::prefix('admin')->group(function () {

    // Auth admin
    Route::post('/login',  [AdminAuthController::class, 'login']);

    // Routes protégées admin
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/profil',  [AdminAuthController::class, 'profil']);

        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Utilisateurs
        Route::get('/utilisateurs',
            [AdminController::class, 'utilisateurs']);
        Route::get('/utilisateurs/{id}',
            [AdminController::class, 'detailUtilisateur']);
        Route::put('/utilisateurs/{id}/toggle',
            [AdminController::class, 'toggleUtilisateur']);
        Route::delete('/utilisateurs/{id}',
            [AdminController::class, 'supprimerUtilisateur']);

        // Agents
        Route::get('/agents',
            [AdminController::class, 'agents']);
        Route::get('/agents/{id}',
            [AdminController::class, 'detailAgent']);
        Route::post('/agents/{id}/valider-cni',
            [AdminController::class, 'validerCNI']);
        Route::put('/agents/{id}/activer',
            [AdminController::class, 'activerAgent']);
        Route::put('/agents/{id}/suspendre',
            [AdminController::class, 'suspendreAgent']);
        Route::delete('/agents/{id}',
            [AdminController::class, 'supprimerAgent']);

        // Annonces
        Route::get('/annonces',
            [AdminController::class, 'annonces']);
        Route::put('/annonces/{id}/moderer',
            [AdminController::class, 'modererAnnonce']);
        Route::delete('/annonces/{id}',
            [AdminController::class, 'supprimerAnnonce']);

        // Plans
        Route::get('/plans',
            [AdminController::class, 'plans']);
        Route::post('/plans',
            [AdminController::class, 'creerPlan']);
        Route::put('/plans/{id}',
            [AdminController::class, 'modifierPlan']);

        // Paiements
        Route::get('/paiements',
            [AdminController::class, 'paiements']);
    });
});

// Routes chat utilisateur
Route::middleware('auth:sanctum')
    ->prefix('utilisateur')
    ->group(function () {
        Route::get('/chat/{idAgent}',
            [ChatController::class, 'conversation']);
        Route::post('/chat/envoyer',
            [ChatController::class, 'envoyerUtilisateur']);
    });

// // Routes chat agent
// Route::middleware('auth:sanctum')
//     ->prefix('agent')
//     ->group(function () {
//         Route::get('/conversations',
//             [ChatController::class, 'conversationsAgent']);
//         Route::get('/chat/{idUtilisateur}',
//             [ChatController::class, 'conversationAgent']);
//         Route::post('/chat/envoyer',
//             [ChatController::class, 'envoyerAgent']);
//         Route::get('/chat/non-lus',
//             [ChatController::class, 'nonLusAgent']);
//     });



    // Route::middleware('auth:sanctum')
    // ->prefix('utilisateur')
    // ->group(function () {
    //     // Favoris
    //     Route::get('/favoris',
    //         [FavoriController::class, 'index']);
    //     Route::post('/favoris/{idAnnonce}/toggle',
    //         [FavoriController::class, 'toggle']);
    //     Route::get('/favoris/{idAnnonce}/verifier',
    //         [FavoriController::class, 'verifier']);
    //     Route::delete('/favoris',
    //         [FavoriController::class, 'vider']);
    // });

//   Route::prefix('chat')->group(function () {

//     Route::post('/demarrer', [ChattController::class, 'demarrerConversation']);
//     Route::post('/envoyer', [ChattController::class, 'envoyerMessage']);
//     Route::get('/messages/{id}', [ChattController::class, 'messages']);
//     Route::get('/conversations/utilisateur/{id}', [ChattController::class, 'conversationsUtilisateur']);
//     Route::get('/conversations/agent/{id}', [ChatController::class, 'conversationsAgent']);
// });



// ── routes/api.php ──────────────────────────────────────────
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/conversations',                  [ChatController::class, 'creerOuRecuperer']);
//     Route::get('/conversations',                   [ChatController::class, 'mesConversations']);
//     Route::get('/conversations/{id}/messages',     [ChatController::class, 'lireMessages']);
//     Route::post('/conversations/{id}/messages',    [ChatController::class, 'envoyerMessage']);
//     Route::get('/conversations/non-lus/count',     [ChatController::class, 'compteurNonLus']);
// });

// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/conversations',               [ChatController::class, 'creerOuRecuperer']);
//     Route::get('/conversations',                [ChatController::class, 'mesConversations']);
//     Route::get('/conversations/non-lus/count',  [ChatController::class, 'compteurNonLus']);
//     Route::get('/conversations/{id}/messages',  [ChatController::class, 'lireMessages']);
//     Route::post('/conversations/{id}/messages', [ChatController::class, 'envoyerMessage']);
// });

// Remplace auth:sanctum par auth:utilisateur,agent
Route::middleware('auth:utilisateur,agent')->group(function () {
    Route::post('/conversations',               [ChatController::class, 'creerOuRecuperer']);
    Route::get('/conversations',                [ChatController::class, 'mesConversations']);
    Route::get('/conversations/non-lus/count',  [ChatController::class, 'compteurNonLus']);
    Route::get('/conversations/{id}/messages',  [ChatController::class, 'lireMessages']);
    Route::post('/conversations/{id}/messages', [ChatController::class, 'envoyerMessage']);
});
// // ── Favoris ───────────────────────────────────────────────────
// Route::middleware('auth:sanctum')->prefix('favoris')->group(function () {
//     Route::post('toggle',          [FavoriController::class, 'toggle']);
//     Route::get('/',                [FavoriController::class, 'mesFavoris']);
//     Route::get('ids',              [FavoriController::class, 'mesIds']);
// });Route::delete('/favoris/vider', [FavoriController::class, 'viderTout'])
//     ->middleware('auth:sanctum');

Route::middleware('auth:utilisateur,agent')->prefix('favoris')->group(function () {
    Route::get('ids',    [FavoriController::class, 'mesIds']);
    Route::get('/',      [FavoriController::class, 'index']);
    Route::post('toggle',[FavoriController::class, 'toggle']);
    Route::delete('vider',[FavoriController::class, 'viderTout']);
});    

// // ✅ Ajoute $request en paramètre de chaque closure
// Route::post('/ia/recommander', function (Request $request) {
//     $response = Http::post('http://127.0.0.1:8001/api/ia/recommander', 
//         $request->all());
//     return $response->json();
// });

// Route::post('/ia/recherche-contextuelle', function (Request $request) {
//     $response = Http::post('http://127.0.0.1:8001/api/ia/recherche-contextuelle', 
//         $request->all());
//     return $response->json();
// });

// Route::post('/ia/recherche-description', function (Request $request) {
//     $response = Http::post('http://127.0.0.1:8001/api/ia/recherche-description', 
//         $request->all());
//     return $response->json();
// });

// routes/web.php — section admin CNI à ajouter

use App\Http\Controllers\Admin\CniController;

// Groupe admin avec middleware auth + admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // ── CNI ──────────────────────────────────────
    Route::prefix('cni')->name('cni.')->group(function () {

        Route::get ('/',                    [CniController::class, 'index'])    ->name('index');
        Route::get ('/verifier',            [CniController::class, 'verifier']) ->name('verifier');
        Route::get ('/{agent}',             [CniController::class, 'show'])     ->name('show');
        Route::get ('/image/{agent}',       [CniController::class, 'image'])    ->name('image');
        Route::get ('/stats/json',          [CniController::class, 'stats'])    ->name('stats');

        Route::post('/analyser',            [CniController::class, 'analyser']) ->name('analyser');
        Route::post('/valider/{agent}',     [CniController::class, 'valider'])  ->name('valider');
        Route::post('/decision/{agent}',    [CniController::class, 'decision']) ->name('decision');
    });
});
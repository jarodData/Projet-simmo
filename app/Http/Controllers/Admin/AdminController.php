<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Models\AgentImmobilier;
use App\Models\Annonce;
use App\Models\DocumentCni;
use App\Models\Plan;
use App\Models\Paiement;
use App\Models\StatistiqueAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class AdminController extends Controller
{
    // ============================================
    // DASHBOARD
    // ============================================
    public function dashboard()
    {
        $stats = [
            // Utilisateurs
            'total_utilisateurs'    => Utilisateur::count(),
            'utilisateurs_verifies' => Utilisateur::where('is_verified', true)->count(),
            'nouveaux_ce_mois'      => Utilisateur::whereMonth('created_at', now()->month)->count(),

            // Agents
            'total_agents'          => AgentImmobilier::count(),
            'agents_actifs'         => AgentImmobilier::where('statut', 'actif')->count(),
            'agents_en_attente'     => AgentImmobilier::where('statut', 'en_attente')->count(),
            'agents_suspendus'      => AgentImmobilier::where('statut', 'suspendu')->count(),

            // Annonces
            'total_annonces'        => Annonce::count(),
            'annonces_actives'      => Annonce::where('statut', 'active')->count(),
            'annonces_ce_mois'      => Annonce::whereMonth('created_at', now()->month)->count(),

            // Paiements
            'total_paiements'       => Paiement::where('statut', 'succes')->count(),
            'revenus_ce_mois'       => Paiement::where('statut', 'succes')
                ->whereMonth('created_at', now()->month)
                ->sum('montant'),
            'revenus_total'         => Paiement::where('statut', 'succes')->sum('montant'),
        ];;

        // CNI en attente de validation
        $cniEnAttente = DocumentCni::with('agent')
            ->where('statut_verification', 'en_attente')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Derniers agents inscrits
        $derniersAgents = AgentImmobilier::with('plan')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Derniers utilisateurs
        $derniersUtilisateurs = Utilisateur::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Évolution mensuelle (6 derniers mois)
        $evolutionMensuelle = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $evolutionMensuelle[] = [
                'mois'         => $date->format('M Y'),
                'utilisateurs' => Utilisateur::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'agents'       => AgentImmobilier::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'annonces'     => Annonce::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'revenus'      => Paiement::where('statut', 'succes')
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->sum('montant'),
            ];
        }

        return response()->json([
            'stats'                => $stats,
            'cni_en_attente'       => $cniEnAttente,
            'derniers_agents'      => $derniersAgents,
            'derniers_utilisateurs'=> $derniersUtilisateurs,
            'evolution_mensuelle'  => $evolutionMensuelle,
        ]);
    }

    // ============================================
    // GESTION UTILISATEURS
    // ============================================

    //  Liste des utilisateurs
    public function utilisateurs(Request $request)
    {
        $query = Utilisateur::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhere('prenom', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('type_user')) {
            $query->where('type_user', $request->type_user);
        }

        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }

        $utilisateurs = $query->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($utilisateurs);
    }

    //  Détail d'un utilisateur
    public function detailUtilisateur($id)
    {
        $utilisateur = Utilisateur::with([
            'historiques',
            'contacts.annonce',
        ])->findOrFail($id);

        return response()->json([
            'utilisateur'         => $utilisateur,
            'nb_recherches'       => $utilisateur->historiques->count(),
            'nb_contacts'         => $utilisateur->contacts->count(),
        ]);
    }

    //  Activer / Désactiver un utilisateur
    public function toggleUtilisateur($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->update([
            'is_verified' => !$utilisateur->is_verified,
        ]);

        $statut = $utilisateur->is_verified ? 'activé' : 'désactivé';
        return response()->json([
            'message'      => "Utilisateur {$statut} avec succès.",
            'is_verified'  => $utilisateur->is_verified,
        ]);
    }

    //  Supprimer un utilisateur
    public function supprimerUtilisateur($id)
    {
        $utilisateur = Utilisateur::findOrFail($id);
        $utilisateur->delete();
        return response()->json([
            'message' => 'Utilisateur supprimé avec succès.',
        ]);
    }

    // ============================================
    // GESTION AGENTS
    // ============================================

    //  Liste des agents
    public function agents(Request $request)
    {
        $query = AgentImmobilier::with('plan', 'documents');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhere('prenom', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $agents = $query->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($agents);
    }

    //  Détail d'un agent
    public function detailAgent($id)
    {
        $agent = AgentImmobilier::with([
            'plan',
            'documents',
            'annonces.localisation',
            'annonces.categorie',
            'contacts',
            'statistiques',
        ])->findOrFail($id);

        $statsGlobales = [
            'total_annonces'    => $agent->annonces->count(),
            'annonces_actives'  => $agent->annonces
                ->where('statut', 'active')->count(),
            'total_contacts'    => $agent->contacts->count(),
            'total_vues'        => $agent->annonces->sum('vues'),
        ];

        return response()->json([
            'agent'          => $agent,
            'stats_globales' => $statsGlobales,
        ]);
    }

    //  Valider la CNI et activer le compte agent
    public function validerCNI(Request $request, $idAgent)
    {
        $request->validate([
            'decision'     => 'required|in:valider,rejeter',
            'commentaire'  => 'nullable|string|max:500',
        ]);

        $agent     = AgentImmobilier::findOrFail($idAgent);
        $document  = DocumentCni::where('id_agent', $idAgent)
            ->latest()->first();

        if (!$document) {
            return response()->json([
                'message' => 'Aucun document CNI trouvé.',
            ], 404);
        }

        if ($request->decision === 'valider') {
            // Valider le document
            $document->update([
                'statut_verification' => 'valide',
                'commentaire_admin'   => $request->commentaire,
            ]);

            // Activer l'agent avec le plan Starter (gratuit)
            $planStarter = Plan::where('nom_plan', 'Starter')->first();
            $agent->update([
                'statut'               => 'actif',
                'id_plan'              => $planStarter?->id,
                'date_souscription'    => now(),
                'date_expiration_plan' => now()->addDays(30),
            ]);

            return response()->json([
                'message' => ' CNI validée ! Compte agent activé avec 30 jours d\'essai gratuit.',
                'agent'   => $agent->fresh('plan'),
            ]);

        } else {
            // Rejeter le document
            $document->update([
                'statut_verification' => 'rejete',
                'commentaire_admin'   => $request->commentaire
                    ?? 'Document non conforme.',
            ]);

            return response()->json([
                'message' => 'CNI rejetée. L\'agent a été notifié.',
            ]);
        }
    }

    //  Activer un agent
    public function activerAgent($id)
    {
        $agent = AgentImmobilier::findOrFail($id);
        $agent->update(['statut' => 'actif']);
        return response()->json([
            'message' => 'Agent activé avec succès.',
            'statut'  => 'actif',
        ]);
    }

    // Suspendre un agent
    public function suspendreAgent($id)
    {
        $agent = AgentImmobilier::findOrFail($id);
        $agent->update(['statut' => 'suspendu']);
        return response()->json([
            'message' => 'Agent suspendu.',
            'statut'  => 'suspendu',
        ]);
    }

    //  Supprimer un agent
    public function supprimerAgent($id)
    {
        $agent = AgentImmobilier::findOrFail($id);

        // Supprimer les photos des annonces
        foreach ($agent->annonces as $annonce) {
            foreach ($annonce->photos as $photo) {
                Storage::disk('public')->delete($photo->chemin_image);
            }
        }

        // Supprimer les CNI
        foreach ($agent->documents as $doc) {
            Storage::disk('public')->delete($doc->chemin_fichier);
        }

        $agent->delete();
        return response()->json([
            'message' => 'Agent supprimé avec succès.',
        ]);
    }

    // ============================================
    // GESTION ANNONCES
    // ============================================

    //  Liste de toutes les annonces
    public function annonces(Request $request)
    {
        $query = Annonce::with(
            'localisation', 'categorie',
            'photoPrincipale', 'agent'
        );

        if ($request->filled('search')) {
            $query->where('titre', 'like', "%{$request->search}%");
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('ville')) {
            $query->whereHas('localisation', function($q) use ($request) {
                $q->where('ville', 'like', "%{$request->ville}%");
            });
        }

        $annonces = $query->orderBy('created_at', 'desc')->paginate(15);
        return response()->json($annonces);
    }

    //  Modérer une annonce (changer le statut)
    public function modererAnnonce(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:active,inactive,vendu',
        ]);

        $annonce = Annonce::findOrFail($id);
        $annonce->update(['statut' => $request->statut]);

        return response()->json([
            'message' => 'Statut de l\'annonce mis à jour.',
            'statut'  => $request->statut,
        ]);
    }

    // Supprimer une annonce
    public function supprimerAnnonce($id)
    {
        $annonce = Annonce::findOrFail($id);
        foreach ($annonce->photos as $photo) {
            Storage::disk('public')->delete($photo->chemin_image);
        }
        $annonce->delete();
        return response()->json([
            'message' => 'Annonce supprimée.',
        ]);
    }

    // ============================================
    // GESTION PLANS
    // ============================================

    //  Liste des plans
    public function plans()
    {
        $plans = Plan::withCount('agents')->get();
        return response()->json($plans);
    }

    //  Créer un plan
    public function creerPlan(Request $request)
    {
        $request->validate([
            'nom_plan'         => 'required|string|max:100',
            'prix_mensuel'     => 'required|numeric|min:0',
            'duree_essai_jours'=> 'integer|min:0',
            'nb_annonces_max'  => 'required|integer|min:1',
            'fonctionnalites'  => 'nullable|array',
        ]);

        $plan = Plan::create([
            'nom_plan'          => $request->nom_plan,
            'prix_mensuel'      => $request->prix_mensuel,
            'duree_essai_jours' => $request->duree_essai_jours ?? 0,
            'nb_annonces_max'   => $request->nb_annonces_max,
            'fonctionnalites'   => $request->fonctionnalites ?? [],
            'is_active'         => true,
        ]);

        return response()->json([
            'message' => 'Plan créé avec succès !',
            'plan'    => $plan,
        ], 201);
    }

    // Modifier un plan
    public function modifierPlan(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update($request->only([
            'nom_plan', 'prix_mensuel', 'duree_essai_jours',
            'nb_annonces_max', 'fonctionnalites', 'is_active',
        ]));

        return response()->json([
            'message' => 'Plan modifié avec succès !',
            'plan'    => $plan,
        ]);
    }

    // ============================================
    // GESTION PAIEMENTS
    // ============================================

    // Liste des paiements
    public function paiements(Request $request)
    {
        $query = Paiement::with('agent', 'plan');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('operateur')) {
            $query->where('operateur', $request->operateur);
        }

        $paiements = $query->orderBy('created_at', 'desc')->paginate(20);

        $totaux = [
            'total_succes'     => Paiement::where('statut', 'succes')->sum('montant'),
            'total_en_attente' => Paiement::where('statut', 'en_attente')->count(),
            'total_echecs'     => Paiement::where('statut', 'echec')->count(),
        ];

        return response()->json([
            'paiements' => $paiements,
            'totaux'    => $totaux,
        ]);
    }
}
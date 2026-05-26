<?php
namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\AgentImmobilier;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PlanController extends Controller
{
    //  LISTE DES PLANS DISPONIBLES (Public)
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();

        return response()->json($plans);
    }

    //  SOUSCRIRE À UN PLAN (Agent)
    public function souscrire(Request $request)
    {
        $request->validate([
            'id_plan' => 'required|exists:plans,id',
        ]);

        $agent = $request->user();
        $plan  = Plan::findOrFail($request->id_plan);

        // Vérifier si l'agent a déjà un plan actif non expiré
        if ($agent->date_expiration_plan && Carbon::now()->lt($agent->date_expiration_plan)) {
            return response()->json([
                'message' => 'Vous avez déjà un plan actif jusqu\'au ' .
                             $agent->date_expiration_plan->format('d/m/Y') . '.',
            ], 409);
        }

        // Calculer les dates
        $dateDebut     = Carbon::now();
        $dureeEssai    = $plan->duree_essai_jours > 0 ? $plan->duree_essai_jours : 30;
        $dateExpiration = $dateDebut->copy()->addDays($dureeEssai);

        // Mettre à jour l'agent
        $agent->update([
            'id_plan'              => $plan->id,
            'date_souscription'    => $dateDebut,
            'date_expiration_plan' => $dateExpiration,
            'statut'               => 'actif',
        ]);

        return response()->json([
            'message'          => "Souscription au plan {$plan->nom_plan} réussie !",
            'plan'             => $plan,
            'date_debut'       => $dateDebut->format('d/m/Y'),
            'date_expiration'  => $dateExpiration->format('d/m/Y'),
            'est_gratuit'      => $plan->prix_mensuel == 0,
        ], 200);
    }

    //  MON PLAN ACTUEL (Agent)
    public function monPlan(Request $request)
    {
        $agent = $request->user()->load('plan');

        $estExpire = $agent->date_expiration_plan
            ? Carbon::now()->gt($agent->date_expiration_plan)
            : true;

        $joursRestants = $agent->date_expiration_plan && !$estExpire
            ? Carbon::now()->diffInDays($agent->date_expiration_plan)
            : 0;

        // Nombre d'annonces utilisées
        $annoncesUtilisees = $agent->annonces()
            ->where('statut', 'active')
            ->count();

        $limiteAnnonces = $agent->plan ? $agent->plan->nb_annonces_max : 0;

        return response()->json([
            'plan'              => $agent->plan,
            'date_souscription' => $agent->date_souscription?->format('d/m/Y'),
            'date_expiration'   => $agent->date_expiration_plan?->format('d/m/Y'),
            'est_expire'        => $estExpire,
            'jours_restants'    => $joursRestants,
            'annonces_utilisees'=> $annoncesUtilisees,
            'limite_annonces'   => $limiteAnnonces,
            'quota_restant'     => max(0, $limiteAnnonces - $annoncesUtilisees),
        ]);
    }

    //  RENOUVELER LE PLAN (Agent)
    public function renouveler(Request $request)
    {
        $agent = $request->user();

        if (!$agent->id_plan) {
            return response()->json([
                'message' => 'Vous n\'avez pas de plan actif à renouveler.',
            ], 404);
        }

        $plan           = $agent->plan;
        $dateExpiration = Carbon::now()->addDays(30);

        $agent->update([
            'date_souscription'    => Carbon::now(),
            'date_expiration_plan' => $dateExpiration,
            'statut'               => 'actif',
        ]);

        return response()->json([
            'message'         => "Plan {$plan->nom_plan} renouvelé avec succès !",
            'date_expiration' => $dateExpiration->format('d/m/Y'),
        ]);
    }
}
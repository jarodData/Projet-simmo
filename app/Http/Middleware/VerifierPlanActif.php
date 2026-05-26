<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VerifierPlanActif 
{
     public function handle(Request $request, Closure $next)
    {
        $agent = $request->user();

        if (!$agent) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // Vérifier si le plan a expiré
        if ($agent->date_expiration_plan &&
            Carbon::now()->gt($agent->date_expiration_plan)) {

            return response()->json([
                'message' => 'Votre plan a expiré. Veuillez renouveler votre abonnement pour continuer.',
                'code'    => 'PLAN_EXPIRE',
            ], 403);
        }

        return $next($request);
    }
}

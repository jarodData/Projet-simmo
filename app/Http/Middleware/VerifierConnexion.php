<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifierConnexion
{
    public function handle(Request $request, Closure $next, string $guard = 'utilisateur')
    {
        if (!auth($guard)->check()) {
            return response()->json([
                'message' => 'Vous devez être connecté pour effectuer cette action.',
                'code'    => 'NON_CONNECTE',
                'redirect'=> $guard === 'agent' ? '/auth/agent/login' : '/auth/utilisateur/login',
            ], 401);
        }
        return $next($request);
    }
}
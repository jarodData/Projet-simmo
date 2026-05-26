<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    // CONNEXION ADMIN
    public function login(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'mot_de_passe' => 'required|string',
        ]);

        $admin = Administrateur::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$admin || !Hash::check(
            $request->mot_de_passe,
            $admin->mot_de_passe_hash
        )) {
            return response()->json([
                'message' => 'Email ou mot de passe incorrect.',
            ], 401);
        }

        // Mettre à jour la dernière connexion
        $admin->update([
            'derniere_connexion' => now(),
        ]);

        $token = $admin->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie !',
            'token'   => $token,
            'admin'   => $admin,
        ]);
    }

    //DÉCONNEXION
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.']);
    }

    // PROFIL
    public function profil(Request $request)
    {
        return response()->json($request->user());
    }
}
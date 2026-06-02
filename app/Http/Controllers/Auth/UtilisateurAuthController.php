<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Mail\UtilisateurVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UtilisateurAuthController extends Controller
{
    
        //  INSCRIPTION
   public function register(Request $request)
{
    $request->validate([
        'nom'          => 'required|string|max:100',
        'prenom'       => 'required|string|max:100',
        'email'        => 'required|email|unique:utilisateurs,email',
        'mot_de_passe' => 'required|string|min:6|confirmed',
        'telephone'    => 'nullable|string|max:20',
        'type_user'    => 'required|in:etudiant,famille,professionnel',
    ]);

    $token = Str::random(64);

    $utilisateur = Utilisateur::create([
        'nom'                => $request->nom,
        'prenom'             => $request->prenom,
        'email'              => $request->email,
        'mot_de_passe_hash'  => Hash::make($request->mot_de_passe), 
        'telephone'          => $request->telephone,
        'type_user'          => $request->type_user,
        'token_verification' => $token,
        'is_verified'        => false,
    ]);

    try {
        Mail::to($utilisateur->email)
            ->send(new UtilisateurVerificationMail($utilisateur, $token));
    } catch (\Exception $e) {
        Log::error('Mail error: ' . $e->getMessage()); // ✅ Log importé
        return response()->json([
            'message' => 'Inscription réussie mais échec de l\'envoi du mail.',
            'success' => false,
        ], 500);
    }

    return response()->json([
        'message' => 'Inscription réussie ! Vérifiez votre email.',
        'success' => true,
    ], 201);
}


   public function verify($token)
{
    $utilisateur = Utilisateur::where('token_verification', $token)->first();

    if (!$utilisateur) {
        return response()->json([
            'message' => 'Token invalide',
            'token_recu' => $token
        ], 404);
    }

    $utilisateur->update([
        'is_verified' => true,
        'token_verification' => null,
    ]);

    return response()->json([
        'message' => 'Compte vérifié avec succès'
    ]);
}
    //  CONNEXION
    public function login(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'mot_de_passe' => 'required|string',
        ]);

        $utilisateur = Utilisateur::where('email', $request->email)->first();

        if (!$utilisateur || !Hash::check($request->mot_de_passe, $utilisateur->mot_de_passe_hash)) {
            return response()->json(['message' => 'Email ou mot de passe incorrect.'], 401);
        }

        if (!$utilisateur->is_verified) {
            return response()->json(['message' => 'Veuillez vérifier votre email avant de vous connecter.'], 403);
        }

        $token = $utilisateur->createToken('utilisateur_token')->plainTextToken;

        return response()->json([
            'message'     => 'Connexion réussie !',
            'token'       => $token,
            'utilisateur' => [
                'id'     => $utilisateur->id,
                'prenom' => $utilisateur->prenom,
                'nom'    => $utilisateur->nom,
                'email'  => $utilisateur->email,
                'role'   => $utilisateur->type_user,
            ],
        ], 200);
    }

    //  DECONNEXION
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.'], 200);
    }

    //  PROFIL
    public function profil(Request $request)
    {
        return response()->json($request->user());
    }

    //  MODIFIER PROFIL
    public function updateProfil(Request $request)
    {
        $request->validate([
            'nom'       => 'sometimes|string|max:100',
            'prenom'    => 'sometimes|string|max:100',
            'telephone' => 'sometimes|string|max:20',
            'type_user' => 'sometimes|in:etudiant,famille,professionnel',
        ]);

        $request->user()->update($request->only(['nom', 'prenom', 'telephone', 'type_user']));

        return response()->json([
            'message'     => 'Profil mis à jour avec succès.',
            'utilisateur' => $request->user(),
        ]);
    }

    public function verifyEmail($token)
{
    // 1. Trouver l'utilisateur par token
    $utilisateur = Utilisateur::where('email_verification_token', $token)
        ->where('email_verified_at', null)
        ->first();

    if (!$utilisateur) {
        // Token invalide ou déjà utilisé → rediriger avec erreur
        return redirect(config('app.frontend_url') . '/login.html?verified=error');
    }

    // 2. Activer le compte
    $utilisateur->update([
        'email_verified_at'        => now(),
        'email_verification_token' => null,
        'statut'                   => 'actif',
    ]);

    // 3. Générer un token Sanctum pour auto-login
    $sanctumToken = $utilisateur->createToken('auto-login-verification')->plainTextToken;

    // 4. Rediriger vers le frontend avec le token
    $redirectUrl = config('app.frontend_url')
        . '/login.html'
        . '?verified=success'
        . '&token=' . $sanctumToken
        . '&user=' . urlencode(json_encode([
            'id'     => $utilisateur->id,
            'prenom' => $utilisateur->prenom,
            'nom'    => $utilisateur->nom,
            'email'  => $utilisateur->email,
            'role'   => $utilisateur->type_user,
        ]));

    return redirect($redirectUrl);
}
// app/Models/AgentImmobilier.php

public function getAuthPassword()
{
    return $this->mot_de_passe_hash;
}


}

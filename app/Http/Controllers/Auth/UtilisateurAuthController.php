<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Utilisateur;
use App\Services\BrevoMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UtilisateurAuthController extends Controller
{
    // ── INSCRIPTION ──────────────────────────────────
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

        // Envoi mail via API Brevo (pas SMTP)
        $lien  = url('/api/auth/utilisateur/verify/' . $token);
        $html  = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
                <h2 style='color:#2563eb'>Bienvenue sur SIMMo !</h2>
                <p>Bonjour <strong>{$utilisateur->prenom}</strong>,</p>
                <p>Merci de vous être inscrit. Cliquez sur le bouton ci-dessous pour vérifier votre compte :</p>
                <a href='{$lien}'
                   style='display:inline-block;background:#2563eb;color:white;
                          padding:12px 24px;border-radius:8px;text-decoration:none;
                          font-weight:bold;margin:16px 0'>
                    Vérifier mon compte
                </a>
                <p style='color:#64748b;font-size:13px'>Ce lien expire dans 24h.</p>
                <p style='color:#64748b;font-size:13px'>Si vous n'avez pas créé de compte, ignorez cet email.</p>
            </div>
        ";

        $brevo = new BrevoMailService();
        $brevo->send($utilisateur->email, $utilisateur->prenom, 'Vérifiez votre compte SIMMo', $html);

        return response()->json([
            'message' => 'Inscription réussie ! Vérifiez votre email.',
            'success' => true,
        ], 201);
    }

    // ── VÉRIFICATION EMAIL ───────────────────────────
    public function verify($token)
    {
        $utilisateur = Utilisateur::where('token_verification', $token)->first();

        if (!$utilisateur) {
            return response()->json([
                'message'    => 'Token invalide',
                'token_recu' => $token,
            ], 404);
        }

        $utilisateur->update([
            'is_verified'        => true,
            'token_verification' => null,
        ]);

        return response()->json(['message' => 'Compte vérifié avec succès']);
    }

    // ── CONNEXION ────────────────────────────────────
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

    // ── DÉCONNEXION ──────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.'], 200);
    }

    // ── PROFIL ───────────────────────────────────────
    public function profil(Request $request)
    {
        return response()->json($request->user());
    }

    // ── MODIFIER PROFIL ──────────────────────────────
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
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AgentImmobilier;
use App\Models\DocumentCni;
use App\Services\BrevoMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgentAuthController extends Controller
{
    // ── INSCRIPTION AGENT ────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'nom'           => 'required|string|max:100',
            'prenom'        => 'required|string|max:100',
            'email'         => 'required|email|unique:agents_immobiliers,email',
            'mot_de_passe'  => 'required|string|min:6|confirmed',
            'telephone'     => 'nullable|string|max:20',
            'numero_agence' => 'nullable|string|max:100',
            'document_cni'  => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $token = Str::random(64);

        $agent = AgentImmobilier::create([
            'nom'                => $request->nom,
            'prenom'             => $request->prenom,
            'email'              => $request->email,
            'mot_de_passe_hash'  => Hash::make($request->mot_de_passe),
            'telephone'          => $request->telephone,
            'numero_agence'      => $request->numero_agence,
            'statut'             => 'en_attente',
            'token_verification' => $token,
            'is_verified'        => false,
        ]);

        // Upload CNI
        $cheminFichier = $request->file('document_cni')
                                 ->store('documents/cni', 'public');

        DocumentCni::create([
            'id_agent'       => $agent->id,
            'chemin_fichier' => $cheminFichier,
        ]);

        // Mail via API Brevo
        $lien = url('/api/auth/agent/verify/' . $token);
        $html = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto'>
                <h2 style='color:#2563eb'>Bienvenue sur SIMMo !</h2>
                <p>Bonjour <strong>{$agent->prenom}</strong>,</p>
                <p>Votre inscription agent a bien été prise en compte.</p>
                <p>Vérifiez d'abord votre email en cliquant ci-dessous :</p>
                <a href='{$lien}'
                   style='display:inline-block;background:#2563eb;color:white;
                          padding:12px 24px;border-radius:8px;text-decoration:none;
                          font-weight:bold;margin:16px 0'>
                    Vérifier mon email
                </a>
                <p>Votre CNI sera vérifiée par notre équipe sous 24h.</p>
                <p style='color:#64748b;font-size:13px'>Ce lien expire dans 24h.</p>
            </div>
        ";

        $brevo = new BrevoMailService();
        $brevo->send($agent->email, $agent->prenom, 'Vérifiez votre compte Agent SIMMo', $html);

        return response()->json([
            'message' => 'Inscription agent réussie ! Vérifiez votre email. Votre CNI sera vérifiée sous 24h.',
        ], 201);
    }

    // ── VÉRIFICATION EMAIL AGENT ─────────────────────
    public function verify($token)
    {
        $agent = AgentImmobilier::where('token_verification', $token)->first();

        if (!$agent) {
            return response()->json(['message' => 'Token invalide.'], 404);
        }

        $agent->update([
            'is_verified'        => true,
            'token_verification' => null,
        ]);

        return response()->json([
            'message' => 'Email vérifié ! En attente de validation de votre CNI par notre équipe.',
        ], 200);
    }

    // ── CONNEXION AGENT ──────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'mot_de_passe' => 'required|string',
        ]);

        $agent = AgentImmobilier::where('email', $request->email)->first();

        if (!$agent || !Hash::check($request->mot_de_passe, $agent->mot_de_passe_hash)) {
            return response()->json(['message' => 'Email ou mot de passe incorrect.'], 401);
        }

        if (!$agent->is_verified) {
            return response()->json(['message' => 'Veuillez vérifier votre email.'], 403);
        }

        if ($agent->statut !== 'actif') {
            return response()->json(['message' => 'Votre compte est en attente de validation par notre équipe.'], 403);
        }

        $token = $agent->createToken('agent_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie !',
            'token'   => $token,
            'agent'   => $agent->load('plan'),
        ], 200);
    }

    // ── LISTE AGENTS ─────────────────────────────────
    public function index()
    {
        $agents = AgentImmobilier::where('statut', 'actif')
            ->withCount('annonces')
            ->with('plan')
            ->get();

        return response()->json($agents);
    }

    // ── DÉCONNEXION ──────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.'], 200);
    }

    // ── PROFIL AGENT ─────────────────────────────────
    public function profil(Request $request)
    {
        return response()->json($request->user()->load('plan', 'annonces'));
    }
}

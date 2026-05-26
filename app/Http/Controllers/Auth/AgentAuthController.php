<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AgentImmobilier;
use App\Models\DocumentCni;
use App\Mail\AgentVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgentAuthController extends Controller
{
    //
     //  INSCRIPTION AGENT
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

        // Upload du document CNI
        $cheminFichier = $request->file('document_cni')
                                 ->store('documents/cni', 'public');

        DocumentCni::create([
            'id_agent'      => $agent->id,
            'chemin_fichier'=> $cheminFichier,
        ]); 

        // Envoi du mail
        Mail::to($agent->email)
            ->send(new AgentVerificationMail($agent, $token));

        return response()->json([
            'message' => 'Inscription agent réussie ! Vérifiez votre email. Votre CNI sera vérifiée sous 24h.',
        ], 201);
    }

    //  VERIFICATION EMAIL AGENT
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

    //  CONNEXION AGENT
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

    //  DECONNEXION
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie.'], 200);
    }

    //  PROFIL AGENT
    public function profil(Request $request)
    {
        return response()->json($request->user()->load('plan', 'annonces'));
    }
}

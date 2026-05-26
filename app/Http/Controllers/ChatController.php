<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // ============================================
    // Conversation utilisateur <-> agent
    // ============================================
 public function demarrer(Request $request)
    {
        $request->validate([
            'id_agent' => 'required|exists:agents,id',
        ]);

        $user = auth()->user();

        // Vérifie si la conversation existe déjà
        $conversation = Conversation::where('id_utilisateur', $user->id)
            ->where('id_agent', $request->id_agent)
            ->first();

        // Sinon créer la conversation
        if (!$conversation) {

            $conversation = Conversation::create([
                'id_utilisateur' => $user->id,
                'id_agent'       => $request->id_agent,
            ]);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }
    public function conversation(Request $request, int $idAgent)
    {
        // Vérifier utilisateur connecté
        $utilisateur = auth('utilisateur')->user();

        if (!$utilisateur) {
            return response()->json([
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $messages = Message::with([
                'utilisateur',
                'agent'
            ])
            ->where('id_agent', $idAgent)
            ->where('id_utilisateur', $utilisateur->id)
            ->orderBy('created_at', 'asc')
            ->get();

        // Marquer les messages agent comme lus
        Message::where('id_agent', $idAgent)
            ->where('id_utilisateur', $utilisateur->id)
            ->where('expediteur', 'agent')
            ->where('lu', false)
            ->update([
                'lu' => true
            ]);

        return response()->json($messages);
    }


    // ============================================
    // Envoyer message utilisateur -> agent
    // ============================================

    public function envoyerUtilisateur(Request $request)
    {
        // Vérifier utilisateur connecté
        $utilisateur = auth('utilisateur')->user();

        if (!$utilisateur) {
            return response()->json([
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        // Validation
        $request->validate([
            'id_agent'   => 'required|exists:agents_immobiliers,id',
            'contenu'    => 'required|string|max:1000',
            'id_annonce' => 'nullable|exists:annonces,id',
        ]);

        $message = Message::create([
            'id_utilisateur' => $utilisateur->id,
            'id_agent'       => $request->id_agent,
            'id_annonce'     => $request->id_annonce,
            'expediteur'     => 'utilisateur',
            'contenu'        => $request->contenu,
            'lu'             => false,
        ]);

        return response()->json([
            'message' => 'Message envoyé avec succès',
            'data'    => $message->load([
                'utilisateur',
                'agent'
            ])
        ], 201);
    }


    // ============================================
    // Envoyer message agent -> utilisateur
    // ============================================

    public function envoyerAgent(Request $request)
    {
        // Vérifier agent connecté
        $agent = auth('agent')->user();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent non authentifié'
            ], 401);
        }

        // Validation
        $request->validate([
            'id_utilisateur' => 'required|exists:utilisateurs,id',
            'contenu'        => 'required|string|max:1000',
            'id_annonce'     => 'nullable|exists:annonces,id',
        ]);

        $message = Message::create([
            'id_utilisateur' => $request->id_utilisateur,
            'id_agent'       => $agent->id,
            'id_annonce'     => $request->id_annonce,
            'expediteur'     => 'agent',
            'contenu'        => $request->contenu,
            'lu'             => false,
        ]);

        return response()->json([
            'message' => 'Message envoyé avec succès',
            'data'    => $message->load([
                'utilisateur',
                'agent'
            ])
        ], 201);
    }


    // ============================================
    // Liste conversations agent
    // ============================================

    public function conversationsAgent(Request $request)
    {
        // Vérifier agent connecté
        $agent = auth('agent')->user();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent non authentifié'
            ], 401);
        }

        $idAgent = $agent->id;

        try {

            $conversations = Message::with([
                    'utilisateur',
                    'annonce'
                ])
                ->where('id_agent', $idAgent)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('id_utilisateur')
                ->map(function ($messages) {

                    $dernier = $messages->first();

                    $nonLus = $messages
                        ->where('expediteur', 'utilisateur')
                        ->where('lu', false)
                        ->count();

                    return [
                        'utilisateur'      => $dernier->utilisateur,
                        'dernier_message'  => $dernier->contenu,
                        'date'             => $dernier->created_at,
                        'non_lus'          => $nonLus,
                        'annonce'          => $dernier->annonce,
                    ];
                })
                ->values();

            return response()->json($conversations);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Erreur chargement conversations',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    // ============================================
    // Conversation agent avec utilisateur
    // ============================================

    public function conversationAgent(
        Request $request,
        int $idUtilisateur
    ) {

        // Vérifier agent connecté
        $agent = auth('agent')->user();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent non authentifié'
            ], 401);
        }

        $messages = Message::with([
                'utilisateur',
                'agent'
            ])
            ->where('id_agent', $agent->id)
            ->where('id_utilisateur', $idUtilisateur)
            ->orderBy('created_at', 'asc')
            ->get();

        // Marquer comme lus
        Message::where('id_agent', $agent->id)
            ->where('id_utilisateur', $idUtilisateur)
            ->where('expediteur', 'utilisateur')
            ->where('lu', false)
            ->update([
                'lu' => true
            ]);

        return response()->json($messages);
    }


    // ============================================
    // Nombre messages non lus agent
    // ============================================

    public function nonLusAgent(Request $request)
    {
        // Vérifier agent connecté
        $agent = auth('agent')->user();

        if (!$agent) {
            return response()->json([
                'message' => 'Agent non authentifié'
            ], 401);
        }

        $count = Message::where('id_agent', $agent->id)
            ->where('expediteur', 'utilisateur')
            ->where('lu', false)
            ->count();

        return response()->json([
            'non_lus' => $count
        ]);
    }
}
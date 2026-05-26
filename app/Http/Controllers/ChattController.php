<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Messages;
use Illuminate\Http\Request;

class ChattController extends Controller
{
    //  CORRIGÉ — utilise $request->id_utilisateur au lieu de auth()->id()
    public function demarrerConversation(Request $request)
    {
        $conversation = Conversation::firstOrCreate([
            'id_utilisateur' => $request->id_utilisateur,
            'id_agent'       => $request->id_agent,
        ]);

        return response()->json($conversation);
    }

    public function envoyerMessage(Request $request)
    {
        if (!$request->id_conversation) {
            return response()->json(['message' => 'Conversation manquante'], 400);
        }

        $messagess = Messages::create([
            'id_conversation' => $request->id_conversation,
            'sender_type'     => $request->sender_type,
            'sender_id'       => $request->sender_id,
            'contenu'         => $request->contenu,
        ]);

        Conversation::where('id', $request->id_conversation)
            ->update([
                'dernier_message'    => $request->contenu,
                'dernier_message_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => $messagess,
        ]);
    }

    public function messages($id)
    {
        Messages::where('id_conversation', $id)
            ->where('lu', false)
            ->update(['lu' => true]);

        $messages = Messages::where('id_conversation', $id)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function conversationsUtilisateur($id)
    {
        $conversations = Conversation::where('id_utilisateur', $id)
            ->with('agent')
            ->withCount(['messages as non_lus' => function ($query) {
                $query->where('lu', false)
                      ->where('sender_type', 'agent');
            }])
            ->latest('dernier_message_at')
            ->get()
            ->map(function ($c) {
                return [
                    'id'                 => $c->id,
                    'dernier_message'    => $c->dernier_message,
                    'dernier_message_at' => $c->dernier_message_at,
                    'non_lus'            => $c->non_lus,
                    'agent'              => $c->agent ? [
                        'id'     => $c->agent->id,
                        'prenom' => $c->agent->prenom,
                        'nom'    => $c->agent->nom,
                    ] : null,
                ];
            });

        return response()->json($conversations);
    }

    public function conversationsAgent($id)
    {
        $conversations = Conversation::where('id_agent', $id)
            ->with('utilisateur')
            ->withCount(['messages as non_lus' => function ($query) {
                $query->where('lu', false)
                      ->where('sender_type', 'utilisateur');
            }])
            ->latest('updated_at')
            ->get()
            ->map(function ($c) {
                return [
                    'id'                 => $c->id,
                    'dernier_message'    => $c->dernier_message,
                    'dernier_message_at' => $c->updated_at,
                    'non_lus'            => $c->non_lus,
                    'utilisateur'        => $c->utilisateur ? [
                        'id'     => $c->utilisateur->id,
                        'prenom' => $c->utilisateur->prenom,
                        'nom'    => $c->utilisateur->nom,
                    ] : null,
                ];
            });

        return response()->json($conversations);
    }

    public function nonLusAgent($id)
    {
        return Conversation::where('id_agent', $id)
            ->withCount(['messages as non_lus' => function ($query) {
                $query->where('lu', false)
                    ->where('sender_type', 'utilisateur');
            }])
            ->get();
    }
}

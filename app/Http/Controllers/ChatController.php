<?php
namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Annonce;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // ── Retourne l'ID du user connecté (agent ou utilisateur) ──
   

    public function creerOuRecuperer(Request $request)
{
    try {
        $request->validate(['annonce_id' => 'required|exists:annonces,id']);

        $annonce  = Annonce::findOrFail($request->annonce_id);
        $clientId = $this->authId();
        $agentId  = $annonce->id_agent;

        \Log::info('Chat debug', [
            'annonce_id' => $request->annonce_id,
            'client_id'  => $clientId,
            'agent_id'   => $agentId,
        ]);

        if (!$clientId) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }
        if (!$agentId) {
            return response()->json(['error' => 'Agent introuvable.'], 422);
        }
        if ($clientId === $agentId) {
            return response()->json(['error' => 'Action impossible.'], 403);
        }

        $conversation = Conversation::firstOrCreate(
            ['annonce_id' => $request->annonce_id, 'client_id' => $clientId],
            ['agent_id'   => $agentId]
        );

        return response()->json([
            'conversation_id' => $conversation->id,
            'est_nouvelle'    => $conversation->wasRecentlyCreated,
        ]);

    } catch (\Exception $e) {
        \Log::error('Chat erreur: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    public function mesConversations()
    {
        $userId = $this->authId();

        $conversations = Conversation::with([
                'annonce:id,titre,prix',
                'dernierMessage',
            ])
            ->where('client_id', $userId)
            ->orWhere('agent_id', $userId)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($c) use ($userId) {
                // Charger manuellement car 2 guards différents
                $inter = $c->client_id === $userId
                    ? $c->agent
                    : $c->client;

                return [
                    'id'                => $c->id,
                    'annonce'           => $c->annonce,
                    'interlocuteur'     => $inter ? [
                        'id'    => $inter->id,
                        'prenom'=> $inter->prenom,
                        'nom'   => $inter->nom,
                    ] : null,
                    'dernier_message'   => $c->dernierMessage?->contenu,
                    'dernier_message_at'=> $c->updated_at,
                    'non_lus'           => $c->messages()
                        ->where('expediteur_id', '!=', $userId)
                        ->where('lu', 0)->count(),
                ];
            });

        return response()->json($conversations);
    }

    public function lireMessages($id)
    {
        $userId       = $this->authId();
        $conversation = Conversation::where('id', $id)
            ->where(fn($q) => $q->where('client_id', $userId)
                                ->orWhere('agent_id', $userId))
            ->firstOrFail();

        $messages = Message::where('conversation_id', $id)
            ->orderBy('created_at')
            ->get();

        Message::where('conversation_id', $id)
            ->where('expediteur_id', '!=', $userId)
            ->where('lu', 0)
            ->update(['lu' => 1]);

        return response()->json($messages);
    }

    public function envoyerMessage(Request $request, $id)
    {
        $request->validate(['contenu' => 'required|string|max:2000']);
        $userId       = $this->authId();
        $conversation = Conversation::where('id', $id)
            ->where(fn($q) => $q->where('client_id', $userId)
                                ->orWhere('agent_id', $userId))
            ->firstOrFail();

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'expediteur_id'   => $userId,
            'contenu'         => $request->contenu,
        ]);

        $conversation->touch();

        return response()->json([
            'id'            => $message->id,
            'contenu'       => $message->contenu,
            'expediteur_id' => $message->expediteur_id,
            'created_at'    => $message->created_at,
        ], 201);
    }

    public function compteurNonLus()
    {
        $userId = $this->authId();
        $count  = Message::whereHas('conversation', fn($q) =>
                $q->where('client_id', $userId)->orWhere('agent_id', $userId)
            )
            ->where('expediteur_id', '!=', $userId)
            ->where('lu', 0)
            ->count();

        return response()->json(['count' => $count]);
    }
   private function authId()
{
    return auth('utilisateur')->id()
        ?? auth('agent')->id();  
}
}
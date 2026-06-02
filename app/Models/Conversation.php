<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Utilisateur;
use App\Models\AgentImmobilier;

class Conversation extends Model
{
    protected $table    = 'conversations';
    protected $fillable = [
        'annonce_id',
        'client_id',        // ← était 'utilisateur_id'
        'agent_id',
        'dernier_message_at',
    ];

    protected $casts = ['dernier_message_at' => 'datetime'];

    public function annonce()
    {
        return $this->belongsTo(Annonce::class);
    }

    public function client()
    {
        return $this->belongsTo(Utilisateur::class, 'client_id')
                    ->select('id', 'nom', 'prenom', 'email');
    }

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'agent_id')
                    ->select('id', 'nom', 'prenom', 'email');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function dernierMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function nonLusPour($userId)
    {
        return $this->messages()
            ->where('lu', false)
            ->where('expediteur_id', '!=', $userId)
            ->count();
    }
}
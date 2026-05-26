<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table    = 'Conversations'; // 

    protected $fillable = [
        'id_utilisateur',
        'id_agent',
        'dernier_message',
        'dernier_message_at',
    ];

    public function messages()
    {
        return $this->hasMany(Messages::class, 'id_conversation');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'id_agent');
    }

    public function nonLus()
    {
        return $this->hasMany(Messages::class, 'id_conversation')
                    ->where('lu', false);
    }
}

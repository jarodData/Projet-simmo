<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    // use HasFactory;
        protected $table = 'contacts';

    protected $fillable = [
        'id_user', 'id_agent', 'id_annonce', 'message', 'statut'
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_user');
    }

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'id_agent');
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }

}

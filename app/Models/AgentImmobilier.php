<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

//use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AgentImmobilier extends Authenticatable
{
    use HasFactory;
    use HasApiTokens, Notifiable;

    protected $table = 'agents_immobiliers';

    protected $fillable = [
        'nom', 'prenom', 'email', 'mot_de_passe_hash',
        'telephone', 'numero_agence', 'statut',
        'token_verification', 'is_verified', 'avatar',
        'id_plan', 'date_souscription', 'date_expiration_plan'
    ];

    protected $hidden = [
        'mot_de_passe_hash', 'token_verification'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'date_souscription' => 'datetime',
        'date_expiration_plan' => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan');
    }

    public function documents()
    {
        return $this->hasMany(DocumentCni::class, 'id_agent');
    }

    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'id_agent');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'id_agent');
    }

    public function statistiques()
    {
        return $this->hasMany(StatistiqueAgent::class, 'id_agent');
    }
}

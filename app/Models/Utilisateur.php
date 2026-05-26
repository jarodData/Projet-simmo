<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Utilisateur extends Model
{
    // use HasFactory;

    use HasApiTokens, Notifiable;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom', 'prenom', 'email', 'mot_de_passe_hash',
        'telephone', 'type_user', 'token_verification',
        'is_verified', 'avatar'
    ];

    protected $hidden = [
        'mot_de_passe_hash', 'token_verification'
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    // Dire à Laravel que le mot de passe s'appelle mot_de_passe_hash
    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }

    public function historiques()
    {
        return $this->hasMany(HistoriqueRecherche::class, 'id_user');
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'id_user');
    }

    public function recommandations()
    {
        return $this->hasMany(Recommandation::class, 'id_user');
    }
}

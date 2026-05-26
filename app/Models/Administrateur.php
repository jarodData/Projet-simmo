<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Administrateur extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'administrateurs';

    protected $fillable = [
        'nom', 'prenom', 'email',
        'mot_de_passe_hash', 'role',
        'is_active', 'derniere_connexion',
    ];

    protected $hidden = ['mot_de_passe_hash'];

    protected $casts = [
        'is_active'          => 'boolean',
        'derniere_connexion'  => 'datetime',
    ];

    public function getAuthPassword()
    {
        return $this->mot_de_passe_hash;
    }
}
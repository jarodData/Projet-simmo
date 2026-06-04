<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Utilisateur;

class Favori extends Model
{
   // protected $table    = 'favoris';
    protected $fillable = ['user_id', 'annonce_id']; // ✅ corrigé

public function annonce()
{
    return $this->belongsTo(Annonce::class, 'annonce_id'); // ✅ corrigé
}

public function utilisateur()
{
    return $this->belongsTo(Utilisateur::class, 'user_id'); // ✅ corrigé
}
}
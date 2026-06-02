<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Utilisateur;

class Favori extends Model
{
    protected $table    = 'favoris';
    protected $fillable = ['id_utilisateur', 'id_annonce'];  // ← unifié

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_utilisateur');
    }
}
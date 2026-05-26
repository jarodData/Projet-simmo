<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recommandation extends Model
{
    // use HasFactory;
    protected $table = 'recommandations';

    protected $fillable = [
        'id_user', 'id_annonce', 'score_recommandation',
        'raison', 'est_vue'
    ];

    protected $casts = [
        'est_vue' => 'boolean',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_user');
    }

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }
}

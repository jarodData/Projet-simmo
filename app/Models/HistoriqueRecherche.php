<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriqueRecherche extends Model
{
    // use HasFactory;
    protected $table = 'historique_recherches';

    protected $fillable = [
        'id_user', 'terme_recherche',
        'filtres_appliques', 'nb_resultats'
    ];

    protected $casts = [
        'filtres_appliques' => 'array',
    ];

    public function utilisateur()
    {
        return $this->belongsTo(Utilisateur::class, 'id_user');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScoreIa extends Model
{
    // use HasFactory;
    protected $table = 'scores_ia';

    protected $fillable = [
        'id_annonce', 'score_nlp', 'prix_predit',
        'score_pertinence', 'version_modele'
    ];

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }
}

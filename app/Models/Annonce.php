<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Annonce extends Model
{
    // use HasFactory;
    protected $table = 'annonces';

    protected $fillable = [
        'titre', 'description', 'prix', 'surface_m2',
        'nb_pieces', 'nb_chambres', 'nb_salles_bain',
        'type_transaction', 'statut', 'meuble', 'vues',
        'score_ia', 'id_agent', 'id_categorie', 'id_localisation'
    ];

    protected $casts = [
        'meuble' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'id_agent');
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieBien::class, 'id_categorie');
    }

    public function localisation()
    {
        return $this->belongsTo(Localisation::class, 'id_localisation');
    }

    public function photos()
    {
        return $this->hasMany(PhotoAnnonce::class, 'id_annonce');
    }

    public function photoprincipale()
    {
        return $this->hasOne(PhotoAnnonce::class, 'id_annonce')
                    ->where('est_principale', true);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class, 'id_annonce');
    }

    public function scoreIa()
    {
        return $this->hasOne(ScoreIa::class, 'id_annonce');
    }
}

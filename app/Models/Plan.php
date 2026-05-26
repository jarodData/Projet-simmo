<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
     protected $table = 'plans';

    protected $fillable = [
        'nom_plan', 'prix_mensuel', 'duree_essai_jours',
        'nb_annonces_max', 'fonctionnalites', 'is_active'
    ];

    protected $casts = [
        'fonctionnalites' => 'array',
        'is_active' => 'boolean',
    ];

    public function agents()
    {
        return $this->hasMany(AgentImmobilier::class, 'id_plan');
    }
}

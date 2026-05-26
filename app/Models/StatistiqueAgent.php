<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatistiqueAgent extends Model
{
    // use HasFactory;
    protected $table = 'statistiques_agents';

    protected $fillable = [
        'id_agent', 'mois', 'annee',
        'nb_annonces_publiees', 'nb_contacts_recus',
        'nb_services_rendus', 'nb_vues_total'
    ];

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'id_agent');
    }
}

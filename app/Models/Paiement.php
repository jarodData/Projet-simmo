<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
   // use HasFactory;
    protected $table = 'paiements';

    protected $fillable = [
        'id_agent', 'id_plan', 'operateur',
        'telephone', 'montant', 'reference',
        'statut', 'transaction_id', 'reponse_api',
    ];

    protected $casts = [
        'reponse_api' => 'array',
    ];

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'id_agent');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id_plan');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCni extends Model
{
    use HasFactory;
    protected $table = 'documents_cni';

    protected $fillable = [
        'id_agent', 'chemin_fichier',
        'statut_verification', 'commentaire_admin'
    ];

    public function agent()
    {
        return $this->belongsTo(AgentImmobilier::class, 'id_agent');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoAnnonce extends Model
{
    // use HasFactory;
     protected $table = 'photos_annonces';

    protected $fillable = ['id_annonce', 'chemin_image', 'est_principale'];

    protected $casts = [
        'est_principale' => 'boolean',
    ];

    public function annonce()
    {
        return $this->belongsTo(Annonce::class, 'id_annonce');
    }
}

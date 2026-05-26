<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Localisation extends Model
{
    // use HasFactory;
    protected $table = 'localisations';

    protected $fillable = [
        'pays', 'ville', 'quartier',
        'adresse_complete', 'latitude', 'longitude'
    ];

    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'id_localisation');
    }

}

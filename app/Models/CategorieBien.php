<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieBien extends Model
{
    use HasFactory;
     protected $table = 'categories_bien';

    protected $fillable = ['libelle', 'description', 'icone'];

    public function annonces()
    {
        return $this->hasMany(Annonce::class, 'id_categorie');
    }
}

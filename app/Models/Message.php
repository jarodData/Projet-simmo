<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';
    public $timestamps = false;
    protected $fillable    = ['conversation_id', 'expediteur_id', 'contenu', 'lu'];
    const CREATED_AT       = 'created_at';
    const UPDATED_AT       = null;

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function expediteur()   { return $this->belongsTo(Utilisateur::class, 'expediteur_id'); }
}

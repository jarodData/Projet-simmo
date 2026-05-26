<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Messages extends Model
{
    protected $table = 'messagess';
    protected $fillable = [
        'id_conversation',
        'sender_type',
        'sender_id',
        'contenu',
        'lu',
    ];

    public function conversation()
    {
        return $this->belongsTo(
            Conversation::class,
            'id_conversation'
        );
    }
}
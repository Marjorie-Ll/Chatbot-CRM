<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'content', 'type', 'sender',
        'ai_response', 'metadata', 'confidence_score'
    ];

    protected $casts = [
        'metadata' => 'array',
        'confidence_score' => 'float',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }
}

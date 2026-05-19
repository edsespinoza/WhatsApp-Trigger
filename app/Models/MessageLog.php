<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Nomeado MessageLog para não conflitar com a facade Log do Laravel
class MessageLog extends Model
{
    const UPDATED_AT = null; // tabela só tem created_at

    protected $table = 'logs';

    protected $fillable = [
        'message_id',
        'event',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'message_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = [
        'provider',
        'event',
        'status',
        'payload',
        'response',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response' => 'array',
        ];
    }
}

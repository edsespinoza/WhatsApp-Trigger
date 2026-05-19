<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'tags',
        'opted_in',
    ];

    protected $casts = [
        'tags' => 'array',
        'opted_in' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function scopeOptedIn($query): void
    {
        $query->where('opted_in', true);
    }

    public function scopeWithTag($query, string $tag): void
    {
        $query->whereJsonContains('tags', $tag);
    }

    public function scopeForUser($query, int $userId): void
    {
        $query->where('user_id', $userId);
    }
}

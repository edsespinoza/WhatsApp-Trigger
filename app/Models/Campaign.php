<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_SENDING = 'sending';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'name',
        'message',
        'target_tags',
        'scheduled_at',
        'status',
        'total_contacts',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'target_tags' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappMessages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    // Campanhas agendadas prontas para disparar
    public function scopeDue($query): void
    {
        $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    public function isCompleted(): bool
    {
        return $this->sent_count + $this->failed_count >= $this->total_contacts
            && $this->total_contacts > 0;
    }
}

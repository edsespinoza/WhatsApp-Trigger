<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    const STATUS_PENDING = 'pending';

    const STATUS_QUEUED = 'queued';

    const STATUS_SENT = 'sent';

    const STATUS_DELIVERED = 'delivered';

    const STATUS_READ = 'read';

    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'campaign_id',
        'contact_id',
        'evolution_message_id',
        'status',
        'queued_at',
        'sent_at',
        'delivered_at',
        'error_message',
    ];

    protected $casts = [
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MessageLog::class, 'message_id');
    }

    public function scopePending($query): void
    {
        $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_QUEUED]);
    }
}

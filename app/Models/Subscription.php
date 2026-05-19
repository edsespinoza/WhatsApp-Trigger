<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    const PLAN_FREE = 'free';

    const PLAN_STARTER = 'starter';

    const PLAN_PRO = 'pro';

    const PLAN_ENTERPRISE = 'enterprise';

    // -1 = ilimitado (Enterprise)
    const PLAN_LIMITS = [
        self::PLAN_FREE => 50,
        self::PLAN_STARTER => 2000,
        self::PLAN_PRO => 10000,
        self::PLAN_ENTERPRISE => -1,
    ];

    protected $fillable = [
        'user_id',
        'plan',
        'messages_limit',
        'messages_sent',
        'period_start',
        'period_end',
        'stripe_subscription_id',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasQuota(): bool
    {
        if ($this->messages_limit === -1) {
            return true;
        }

        return $this->messages_sent < $this->messages_limit;
    }

    public function remainingMessages(): int
    {
        if ($this->messages_limit === -1) {
            return PHP_INT_MAX;
        }

        return max(0, $this->messages_limit - $this->messages_sent);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->period_end->isFuture();
    }

    public static function limitForPlan(string $plan): int
    {
        return self::PLAN_LIMITS[$plan] ?? self::PLAN_LIMITS[self::PLAN_FREE];
    }
}

<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan' => Subscription::PLAN_FREE,
            'messages_limit' => Subscription::PLAN_LIMITS[Subscription::PLAN_FREE],
            'messages_sent' => 0,
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ];
    }

    public function starter(): static
    {
        return $this->state([
            'plan' => Subscription::PLAN_STARTER,
            'messages_limit' => Subscription::PLAN_LIMITS[Subscription::PLAN_STARTER],
        ]);
    }

    public function pro(): static
    {
        return $this->state([
            'plan' => Subscription::PLAN_PRO,
            'messages_limit' => Subscription::PLAN_LIMITS[Subscription::PLAN_PRO],
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attrs) => [
            'messages_sent' => $attrs['messages_limit'],
        ]);
    }
}

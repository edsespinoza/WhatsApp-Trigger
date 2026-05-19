<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'message' => fake()->paragraph(),
            'target_tags' => null,
            'scheduled_at' => now()->addHour(),
            'status' => Campaign::STATUS_DRAFT,
            'total_contacts' => 0,
            'sent_count' => 0,
            'failed_count' => 0,
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => Campaign::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(['status' => Campaign::STATUS_COMPLETED]);
    }
}

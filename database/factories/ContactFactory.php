<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'phone' => '5511'.fake()->unique()->numerify('#########'),
            'tags' => null,
            'opted_in' => true,
        ];
    }

    public function withTags(array $tags): static
    {
        return $this->state(['tags' => $tags]);
    }

    public function optedOut(): static
    {
        return $this->state(['opted_in' => false]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@whatstrigger.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        Subscription::firstOrCreate(
            ['user_id' => $user->id],
            [
                'plan' => Subscription::PLAN_STARTER,
                'messages_limit' => Subscription::PLAN_LIMITS[Subscription::PLAN_STARTER],
                'messages_sent' => 0,
                'period_start' => now()->toDateString(),
                'period_end' => now()->addYear()->toDateString(),
                'status' => 'active',
            ]
        );
    }
}

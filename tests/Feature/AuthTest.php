<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/whatstrigger/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'senha123!',
            'password_confirmation' => 'senha123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('users', ['email' => 'joao@example.com']);
        $this->assertDatabaseHas('subscriptions', ['plan' => 'free']);
    }

    public function test_register_creates_free_subscription(): void
    {
        $response = $this->postJson('/api/whatstrigger/register', [
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => 'senha123!',
            'password_confirmation' => 'senha123!',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'maria@example.com')->first();
        $this->assertEquals('free', $user->subscription->plan);
        $this->assertEquals(50, $user->subscription->messages_limit);
    }

    public function test_user_can_login_via_api(): void
    {
        $user = User::factory()->create(['password' => bcrypt('senha123!')]);

        $response = $this->postJson('/api/whatstrigger/login', [
            'email' => $user->email,
            'password' => 'senha123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correta')]);

        $response = $this->postJson('/api/whatstrigger/login', [
            'email' => $user->email,
            'password' => 'errada',
        ]);

        $response->assertStatus(401);
    }
}

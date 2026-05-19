<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_authenticated_user_can_list_contacts(): void
    {
        Contact::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->withToken($this->token)
            ->getJson('/api/whatstrigger/contacts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_only_sees_own_contacts(): void
    {
        Contact::factory()->count(2)->create(['user_id' => $this->user->id]);
        Contact::factory()->count(3)->create(); // outro usuário

        $response = $this->withToken($this->token)
            ->getJson('/api/whatstrigger/contacts');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_can_create_contact(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts', [
                'name' => 'Carlos Aluno',
                'phone' => '5511987654321',
                'tags' => ['aluno'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Carlos Aluno');

        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'phone' => '5511987654321',
        ]);
    }

    public function test_duplicate_phone_for_same_user_is_rejected(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'phone' => '5511999999999',
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts', [
                'name' => 'Outro Nome',
                'phone' => '5511999999999',
            ]);

        $response->assertStatus(422);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/whatstrigger/contacts');

        $response->assertStatus(401);
    }
}

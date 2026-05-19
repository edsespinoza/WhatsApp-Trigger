<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactImportTest extends TestCase
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

    public function test_imports_multiple_contacts(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => [
                    ['name' => 'João', 'phone' => '5511999990001'],
                    ['name' => 'Maria', 'phone' => '5511999990002'],
                    ['name' => 'Pedro', 'phone' => '5511999990003'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['created' => 3, 'skipped' => 0]);

        $this->assertDatabaseCount('contacts', 3);
    }

    public function test_skips_duplicate_phone_for_same_user(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'phone' => '5511999990001',
        ]);

        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => [
                    ['name' => 'João', 'phone' => '5511999990001'],
                    ['name' => 'Maria', 'phone' => '5511999990002'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['created' => 1, 'skipped' => 1]);

        $this->assertDatabaseCount('contacts', 2);
    }

    public function test_import_respects_user_isolation(): void
    {
        $otherUser = User::factory()->create();
        Contact::factory()->create([
            'user_id' => $otherUser->id,
            'phone' => '5511999990001',
        ]);

        // Same phone, different user — should NOT be skipped
        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => [
                    ['name' => 'João', 'phone' => '5511999990001'],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['created' => 1, 'skipped' => 0]);
    }

    public function test_validates_required_fields(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => [
                    ['name' => '', 'phone' => ''],
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_rejects_empty_contacts_array(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => [],
            ]);

        $response->assertStatus(422);
    }

    public function test_rejects_more_than_500_contacts(): void
    {
        $contacts = [];
        for ($i = 0; $i < 501; $i++) {
            $contacts[] = ['name' => "User $i", 'phone' => '55119999'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)];
        }

        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => $contacts,
            ]);

        $response->assertStatus(422);
    }

    public function test_imports_with_tags_and_opt_in(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/whatstrigger/contacts/import', [
                'contacts' => [
                    [
                        'name' => 'João',
                        'phone' => '5511999990001',
                        'tags' => ['vip', 'aluno'],
                        'opted_in' => true,
                    ],
                    [
                        'name' => 'Maria',
                        'phone' => '5511999990002',
                        'opted_in' => false,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['created' => 2]);

        $this->assertEquals(['vip', 'aluno'], Contact::where('phone', '5511999990001')->first()->tags);
        $this->assertFalse(Contact::where('phone', '5511999990002')->first()->opted_in);
    }
}

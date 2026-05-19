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

    // ── Export CSV ─────────────────────────────────────────────────────────

    public function test_export_returns_csv_with_correct_headers(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'João Silva',
            'phone' => '5511999999999',
        ]);

        $response = $this->withToken($this->token)
            ->get('/api/whatstrigger/contacts/export');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="contatos.csv"');
    }

    public function test_export_csv_contains_contact_data(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Maria Souza',
            'phone' => '5511888888888',
            'tags' => ['aluno', 'vip'],
            'opted_in' => true,
        ]);

        $response = $this->withToken($this->token)
            ->get('/api/whatstrigger/contacts/export');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Maria Souza', $content);
        $this->assertStringContainsString('5511888888888', $content);
        $this->assertStringContainsString('aluno; vip', $content);
        $this->assertStringContainsString('Sim', $content);
    }

    public function test_export_only_contains_user_contacts(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Meu Contato',
            'phone' => '5511111111111',
        ]);
        Contact::factory()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Outro Contato',
            'phone' => '5522222222222',
        ]);

        $response = $this->withToken($this->token)
            ->get('/api/whatstrigger/contacts/export');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Meu Contato', $content);
        $this->assertStringNotContainsString('Outro Contato', $content);
    }

    public function test_export_starts_with_bom(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Teste',
            'phone' => '5511000000000',
        ]);

        $response = $this->withToken($this->token)
            ->get('/api/whatstrigger/contacts/export');

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
    }

    public function test_export_has_header_row(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Teste',
            'phone' => '5511000000000',
        ]);

        $response = $this->withToken($this->token)
            ->get('/api/whatstrigger/contacts/export');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Nome', $content);
        $this->assertStringContainsString('Telefone', $content);
        $this->assertStringContainsString('Criado em', $content);
    }

    public function test_export_empty_returns_csv_with_only_header(): void
    {
        $response = $this->withToken($this->token)
            ->get('/api/whatstrigger/contacts/export');

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Nome', $content);
        $this->assertStringContainsString('Telefone', $content);
        $this->assertStringContainsString('Criado em', $content);
        $lines = explode("\n", trim($content));
        $this->assertCount(1, $lines); // header only (BOM is prepended to header)
    }

    public function test_export_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/whatstrigger/contacts/export');

        $response->assertStatus(401);
    }
}

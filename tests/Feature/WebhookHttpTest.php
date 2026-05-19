<?php

namespace Tests\Feature;

use App\Jobs\ProcessEvolutionWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookHttpTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->apiKey = 'test-evolution-key-123';
        Config::set('whatstrigger.evolution.key', $this->apiKey);
    }

    public function test_evolution_webhook_dispatches_job(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => ['id' => 'msg_123'],
                'update' => ['status' => 'DELIVERY_ACK'],
            ],
        ];

        $response = $this->postJson('/api/whatstrigger/webhooks/evolution', $payload, [
            'apikey' => $this->apiKey,
        ]);

        $response->assertStatus(200)
            ->assertJson(['received' => true]);

        Queue::assertPushed(ProcessEvolutionWebhook::class);
    }

    public function test_evolution_webhook_rejects_missing_api_key(): void
    {
        $response = $this->postJson('/api/whatstrigger/webhooks/evolution', [
            'event' => 'messages.update',
            'data' => ['key' => ['id' => 'msg_123']],
        ]);

        $response->assertStatus(401);
        Queue::assertPushed(ProcessEvolutionWebhook::class, 0);
    }

    public function test_evolution_webhook_rejects_wrong_api_key(): void
    {
        $response = $this->postJson('/api/whatstrigger/webhooks/evolution', [
            'event' => 'messages.update',
            'data' => ['key' => ['id' => 'msg_123']],
        ], [
            'apikey' => 'wrong-key',
        ]);

        $response->assertStatus(401);
    }

    public function test_evolution_webhook_validates_required_fields(): void
    {
        $response = $this->postJson('/api/whatstrigger/webhooks/evolution', [
            'event' => '',
            'data' => [],
        ], [
            'apikey' => $this->apiKey,
        ]);

        $response->assertStatus(422);
        Queue::assertPushed(ProcessEvolutionWebhook::class, 0);
    }

    public function test_evolution_webhook_logs_to_database(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => ['id' => 'msg_456'],
            ],
        ];

        $this->postJson('/api/whatstrigger/webhooks/evolution', $payload, [
            'apikey' => $this->apiKey,
        ]);

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'evolution',
            'event' => 'messages.update',
            'status' => 'success',
        ]);
    }

    public function test_stripe_webhook_rejects_without_signature(): void
    {
        $response = $this->postJson('/api/whatstrigger/webhooks/stripe', [
            'type' => 'checkout.session.completed',
        ]);

        $response->assertStatus(400);
    }

    public function test_stripe_webhook_logs_without_signature(): void
    {
        $this->postJson('/api/whatstrigger/webhooks/stripe', [
            'type' => 'checkout.session.completed',
        ]);

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'stripe',
            'status' => 'failed',
        ]);
    }
}

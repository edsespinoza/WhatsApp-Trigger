<?php

namespace Tests\Feature;

use App\Jobs\ProcessEvolutionWebhook;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookEvolutionTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppMessage $message;

    private string $evolutionId;

    protected function setUp(): void
    {
        parent::setUp();

        $contact = Contact::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $contact->user_id]);

        $this->evolutionId = 'evolution_msg_'.fake()->uuid();

        $this->message = WhatsAppMessage::create([
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'evolution_message_id' => $this->evolutionId,
            'status' => WhatsAppMessage::STATUS_SENT,
        ]);
    }

    public function test_updates_status_to_delivered_on_delivery_ack(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => ['id' => $this->evolutionId],
                'update' => ['status' => 'DELIVERY_ACK'],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_DELIVERED, $this->message->status);
        $this->assertNotNull($this->message->delivered_at);
    }

    public function test_updates_status_to_read_on_read_event(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => ['id' => $this->evolutionId],
                'update' => ['status' => 'READ'],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_READ, $this->message->status);
    }

    public function test_updates_status_to_read_on_played_event(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => ['id' => $this->evolutionId],
                'update' => ['status' => 'PLAYED'],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_READ, $this->message->status);
    }

    public function test_silently_ignores_unknown_message_id(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => ['id' => 'nonexistent_evolution_id'],
                'update' => ['status' => 'DELIVERY_ACK'],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_SENT, $this->message->status);
    }

    public function test_silently_ignores_missing_message_id(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => [],
                'update' => ['status' => 'DELIVERY_ACK'],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_SENT, $this->message->status);
    }

    public function test_silently_ignores_unknown_event_type(): void
    {
        $payload = [
            'event' => 'messages.unknown_event',
            'data' => [
                'key' => ['id' => $this->evolutionId],
                'update' => ['status' => 'SOME_STATUS'],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_SENT, $this->message->status);
    }

    public function test_handles_complex_payload_structure(): void
    {
        $payload = [
            'event' => 'messages.update',
            'data' => [
                'key' => [
                    'id' => $this->evolutionId,
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => true,
                ],
                'update' => [
                    'status' => 'DELIVERY_ACK',
                    'timestamp' => time(),
                ],
            ],
        ];

        (new ProcessEvolutionWebhook($payload))->handle();

        $this->message->refresh();
        $this->assertEquals(WhatsAppMessage::STATUS_DELIVERED, $this->message->status);
    }
}

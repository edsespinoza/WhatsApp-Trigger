<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEvolutionWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly array $payload) {}

    public function handle(): void
    {
        $event = $this->payload['event'] ?? null;
        $messageId = $this->payload['data']['key']['id'] ?? null;

        if (! $messageId) {
            return;
        }

        $message = WhatsAppMessage::where('evolution_message_id', $messageId)->first();

        if (! $message) {
            return;
        }

        match ($event) {
            'messages.update' => $this->handleStatusUpdate($message),
            default => null,
        };

        MessageLog::create([
            'message_id' => $message->id,
            'event' => 'webhook_received',
            'payload' => $this->payload,
        ]);
    }

    private function handleStatusUpdate(WhatsAppMessage $message): void
    {
        // STATUS da Evolution API: ERROR, PENDING, SERVER_ACK, DELIVERY_ACK, READ, PLAYED
        $rawStatus = strtoupper($this->payload['data']['update']['status'] ?? '');

        match ($rawStatus) {
            'DELIVERY_ACK' => $message->update([
                'status' => WhatsAppMessage::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]),
            'READ', 'PLAYED' => $message->update([
                'status' => WhatsAppMessage::STATUS_READ,
            ]),
            default => null,
        };
    }
}

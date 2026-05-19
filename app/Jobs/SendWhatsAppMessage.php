<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\MessageLog;
use App\Models\WhatsAppMessage;
use App\Services\EvolutionApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60; // segundos entre retentativas

    public function __construct(public readonly WhatsAppMessage $message) {}

    public function handle(EvolutionApiService $evolution): void
    {
        $response = $evolution->sendText(
            $this->message->contact->phone,
            $this->message->campaign->message,
        );

        $this->message->update([
            'status' => WhatsAppMessage::STATUS_SENT,
            'sent_at' => now(),
            'evolution_message_id' => $response['key']['id'] ?? null,
        ]);

        MessageLog::create([
            'message_id' => $this->message->id,
            'event' => 'sent',
            'payload' => $response,
        ]);

        $this->message->campaign->increment('sent_count');
        $this->checkCampaignCompletion();
    }

    public function failed(Throwable $e): void
    {
        $this->message->update([
            'status' => WhatsAppMessage::STATUS_FAILED,
            'error_message' => $e->getMessage(),
        ]);

        MessageLog::create([
            'message_id' => $this->message->id,
            'event' => 'failed',
            'payload' => ['error' => $e->getMessage()],
        ]);

        $this->message->campaign->increment('failed_count');
        $this->checkCampaignCompletion();
    }

    private function checkCampaignCompletion(): void
    {
        $campaign = $this->message->campaign;

        $hasPending = $campaign->whatsappMessages()
            ->pending()
            ->exists();

        if (! $hasPending) {
            $campaign->update(['status' => Campaign::STATUS_COMPLETED]);
        }
    }
}

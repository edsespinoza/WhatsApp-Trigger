<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class DispatchCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly Campaign $campaign) {}

    public function handle(): void
    {
        $subscription = $this->campaign->user->subscription;

        if (! $subscription || ! $subscription->isActive() || ! $subscription->hasQuota()) {
            $this->campaign->update(['status' => Campaign::STATUS_CANCELLED]);

            return;
        }

        $contacts = $this->resolveContacts($subscription->remainingMessages());

        if ($contacts->isEmpty()) {
            $this->campaign->update(['status' => Campaign::STATUS_COMPLETED]);

            return;
        }

        // Inserção em lote evita N queries para grandes listas
        $now = now();
        $rows = $contacts->map(fn ($c) => [
            'campaign_id' => $this->campaign->id,
            'contact_id' => $c->id,
            'status' => WhatsAppMessage::STATUS_QUEUED,
            'queued_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        DB::table('whatsapp_messages')->insert($rows);

        $this->campaign->update([
            'status' => Campaign::STATUS_SENDING,
            'total_contacts' => count($rows),
        ]);

        // Incrementa o contador da assinatura antecipadamente para bloquear quota
        $subscription->increment('messages_sent', count($rows));

        $this->dispatchSendJobs();
    }

    private function resolveContacts(int $quota)
    {
        $query = Contact::forUser($this->campaign->user_id)->optedIn();

        if (! empty($this->campaign->target_tags)) {
            foreach ($this->campaign->target_tags as $tag) {
                $query->withTag($tag);
            }
        }

        return $query->limit($quota)->get(['id']);
    }

    private function dispatchSendJobs(): void
    {
        $messagesPerMinute = config('whatstrigger.rate_limit.messages_per_minute', 10);

        WhatsAppMessage::where('campaign_id', $this->campaign->id)
            ->where('status', WhatsAppMessage::STATUS_QUEUED)
            ->get(['id'])
            ->each(function (WhatsAppMessage $message, int $index) use ($messagesPerMinute) {
                // Espalha os envios no tempo para respeitar o aquecimento gradual
                $delaySecs = (int) floor($index / $messagesPerMinute) * 60;

                SendWhatsAppMessage::dispatch($message)
                    ->delay(now()->addSeconds($delaySecs));
            });
    }
}

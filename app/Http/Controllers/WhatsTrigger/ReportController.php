<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\WhatsAppMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function campaign(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        $counts = $campaign->whatsappMessages()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = max($campaign->total_contacts, 1);
        $sent = $counts->get(WhatsAppMessage::STATUS_SENT, 0)
                  + $counts->get(WhatsAppMessage::STATUS_DELIVERED, 0)
                  + $counts->get(WhatsAppMessage::STATUS_READ, 0);
        $delivered = $counts->get(WhatsAppMessage::STATUS_DELIVERED, 0)
                   + $counts->get(WhatsAppMessage::STATUS_READ, 0);

        // Linha do tempo de envios agrupada por hora
        $timeline = $campaign->whatsappMessages()
            ->whereNotNull('sent_at')
            ->selectRaw("DATE_FORMAT(sent_at, '%Y-%m-%d %H:00:00') as hour, COUNT(*) as count")
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        return response()->json([
            'campaign' => $campaign,
            'stats' => [
                'total' => $campaign->total_contacts,
                'sent' => $sent,
                'delivered' => $delivered,
                'read' => $counts->get(WhatsAppMessage::STATUS_READ, 0),
                'failed' => $counts->get(WhatsAppMessage::STATUS_FAILED, 0),
                'pending' => $counts->get(WhatsAppMessage::STATUS_PENDING, 0)
                                 + $counts->get(WhatsAppMessage::STATUS_QUEUED, 0),
                'delivery_rate' => round(($delivered / $total) * 100, 1),
            ],
            'timeline' => $timeline,
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;

        $campaignStats = Campaign::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'subscription' => [
                'plan' => $subscription?->plan ?? 'free',
                'status' => $subscription?->status ?? 'active',
                'messages_sent' => $subscription?->messages_sent ?? 0,
                'messages_limit' => $subscription?->messages_limit ?? 50,
                'remaining' => $subscription?->remainingMessages() ?? 0,
                'period_end' => $subscription?->period_end,
            ],
            'campaigns' => [
                'total' => $campaignStats->sum(),
                'sending' => $campaignStats->get(Campaign::STATUS_SENDING, 0),
                'scheduled' => $campaignStats->get(Campaign::STATUS_SCHEDULED, 0),
                'completed' => $campaignStats->get(Campaign::STATUS_COMPLETED, 0),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Http\Requests\WhatsTrigger\StoreCampaignRequest;
use App\Jobs\DispatchCampaign;
use App\Models\Campaign;
use App\Models\WhatsAppMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CampaignController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($campaigns);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = Campaign::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
            'status' => Campaign::STATUS_DRAFT,
        ]);

        return response()->json($campaign, 201);
    }

    public function show(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);

        return response()->json($campaign);
    }

    public function update(StoreCampaignRequest $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);
        abort_if($campaign->status !== Campaign::STATUS_DRAFT, 422, 'Apenas campanhas em rascunho podem ser editadas.');

        $campaign->update($request->validated());

        return response()->json($campaign->fresh());
    }

    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);
        abort_if(
            ! in_array($campaign->status, [Campaign::STATUS_DRAFT, Campaign::STATUS_CANCELLED]),
            422,
            'Apenas campanhas em rascunho ou canceladas podem ser excluídas.'
        );

        $campaign->delete();

        return response()->json(null, 204);
    }

    public function send(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);
        abort_if(
            ! in_array($campaign->status, [Campaign::STATUS_DRAFT, Campaign::STATUS_SCHEDULED]),
            422,
            'Campanha não pode ser disparada neste status.'
        );

        $request->validate([
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        if ($request->scheduled_at) {
            $campaign->update([
                'status' => Campaign::STATUS_SCHEDULED,
                'scheduled_at' => $request->scheduled_at,
            ]);
        } else {
            $campaign->update([
                'status' => Campaign::STATUS_SCHEDULED,
                'scheduled_at' => now(),
            ]);
            DispatchCampaign::dispatch($campaign);
        }

        return response()->json($campaign->fresh());
    }

    public function cancel(Request $request, Campaign $campaign): JsonResponse
    {
        abort_if($campaign->user_id !== $request->user()->id, 403);
        abort_if(
            ! in_array($campaign->status, [Campaign::STATUS_SCHEDULED, Campaign::STATUS_SENDING]),
            422,
            'Apenas campanhas agendadas ou em envio podem ser canceladas.'
        );

        // Mensagens ainda pendentes passam para failed para liberar o slot de quota
        $cancelled = $campaign->whatsappMessages()
            ->pending()
            ->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error_message' => 'Campanha cancelada pelo usuário.',
            ]);

        $campaign->update([
            'status' => Campaign::STATUS_CANCELLED,
            'failed_count' => $campaign->failed_count + $cancelled,
        ]);

        return response()->json($campaign->fresh());
    }
}

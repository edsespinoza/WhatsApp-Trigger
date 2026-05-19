<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEvolutionWebhook;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe
    ) {}

    public function evolution(Request $request): JsonResponse
    {
        $request->validate([
            'event' => 'required|string',
            'data' => 'required|array',
        ]);

        ProcessEvolutionWebhook::dispatch($request->all());

        return response()->json(['received' => true]);
    }

    public function stripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (! $sigHeader) {
            return response()->json(['message' => 'Missing signature.'], 400);
        }

        try {
            $result = $this->stripe->handleWebhook($payload, $sigHeader);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        return response()->json($result);
    }
}

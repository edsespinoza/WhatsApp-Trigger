<?php

namespace App\Http\Controllers\WhatsTrigger;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly StripeService $stripe
    ) {}

    public function show(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription;

        return response()->json([
            'subscription' => $subscription,
            'plans' => $this->plansWithLimits(),
        ]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|in:starter,pro,enterprise',
        ]);

        $subscription = $request->user()->subscription
            ?? Subscription::where('user_id', $request->user()->id)->firstOrFail();

        $plan = $request->plan;

        if (! $this->stripe->isConfigured()) {
            return response()->json([
                'message' => 'Pagamento via Stripe não configurado. Entre em contato para upgrade manual.',
                'plan' => $plan,
                'messages_limit' => Subscription::limitForPlan($plan),
                'current_plan' => $subscription->plan,
            ], 202);
        }

        $checkout = $this->stripe->createCheckoutSession($subscription, $plan);

        return response()->json([
            'message' => 'Redirecionando para checkout...',
            'checkout_url' => $checkout['url'],
            'plan' => $plan,
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $subscription = $request->user()->subscription;

        abort_if(! $subscription || $subscription->status === 'cancelled', 422, 'Assinatura já cancelada ou inexistente.');

        $subscription->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Assinatura cancelada. O acesso segue até o fim do período.']);
    }

    private function plansWithLimits(): array
    {
        return collect(Subscription::PLAN_LIMITS)
            ->map(fn ($limit, $plan) => [
                'plan' => $plan,
                'messages_limit' => $limit,
            ])
            ->values()
            ->toArray();
    }
}

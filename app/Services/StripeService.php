<?php

namespace App\Services;

use App\Models\Subscription;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    private string $secretKey;

    private string $webhookSecret;

    private string $priceId;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret_key');
        $this->webhookSecret = config('services.stripe.webhook_secret');
        $this->priceId = config('services.stripe.price_id');
    }

    public function isConfigured(): bool
    {
        return $this->secretKey !== '' && $this->secretKey !== null;
    }

    public function createCheckoutSession(Subscription $subscription, string $plan): array
    {
        Stripe::setApiKey($this->secretKey);

        $priceId = match ($plan) {
            'starter' => config('services.stripe.prices.starter'),
            'pro' => config('services.stripe.prices.pro'),
            'enterprise' => config('services.stripe.prices.enterprise'),
            default => throw new \InvalidArgumentException("Plano inválido: {$plan}"),
        };

        $session = Session::create([
            'mode' => 'subscription',
            'client_reference_id' => (string) $subscription->user_id,
            'metadata' => [
                'subscription_id' => (string) $subscription->id,
                'plan' => $plan,
            ],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => url('/whatstrigger/subscription?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url' => url('/whatstrigger/subscription'),
        ]);

        return [
            'url' => $session->url,
            'id' => $session->id,
        ];
    }

    public function handleWebhook(string $payload, string $sigHeader): array
    {
        Stripe::setApiKey($this->secretKey);

        $event = Webhook::constructEvent($payload, $sigHeader, $this->webhookSecret);

        return match ($event->type) {
            'checkout.session.completed' => $this->onCheckoutCompleted($event->data->object),
            'customer.subscription.updated' => $this->onSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->onSubscriptionDeleted($event->data->object),
            default => ['received' => true, 'type' => $event->type, 'handled' => false],
        };
    }

    private function onCheckoutCompleted(object $session): array
    {
        $userId = (int) $session->client_reference_id;
        $plan = $session->metadata->plan;

        $subscription = Subscription::where('user_id', $userId)->first();

        if (! $subscription) {
            return ['received' => true, 'error' => 'Assinatura não encontrada'];
        }

        $subscription->update([
            'plan' => $plan,
            'messages_limit' => Subscription::limitForPlan($plan),
            'stripe_subscription_id' => $session->subscription,
            'status' => 'active',
            'period_start' => now()->toDateString(),
            'period_end' => now()->addMonth()->toDateString(),
        ]);

        return ['received' => true, 'type' => 'checkout.session.completed', 'handled' => true];
    }

    private function onSubscriptionUpdated(object $stripeSubscription): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if (! $subscription) {
            return ['received' => true, 'error' => 'Assinatura não encontrada'];
        }

        $subscription->update([
            'status' => $stripeSubscription->status === 'active' ? 'active' : 'past_due',
        ]);

        return ['received' => true, 'type' => 'customer.subscription.updated', 'handled' => true];
    }

    private function onSubscriptionDeleted(object $stripeSubscription): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $subscription->update(['status' => 'cancelled']);
        }

        return ['received' => true, 'type' => 'customer.subscription.deleted', 'handled' => true];
    }
}

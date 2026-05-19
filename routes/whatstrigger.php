<?php

use App\Http\Controllers\WhatsTrigger\AuthController;
use App\Http\Controllers\WhatsTrigger\CampaignController;
use App\Http\Controllers\WhatsTrigger\ContactController;
use App\Http\Controllers\WhatsTrigger\ReportController;
use App\Http\Controllers\WhatsTrigger\SubscriptionController;
use App\Http\Controllers\WhatsTrigger\WebhookController;
use App\Http\Controllers\WhatsTrigger\WhatsAppController;
use App\Http\Middleware\VerifyEvolutionWebhook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

// Registrar em routes/api.php:
//   require __DIR__ . '/whatstrigger.php';

// Prefixo de nome 'wt.api.' evita conflito com as rotas web que usam 'wt.'
Route::prefix('whatstrigger')->name('wt.api.')->group(function () {

    // ── Health Check (sem autenticação) ───────────────────────────────────────
    Route::get('health', function () {
        $db = false;
        $redis = false;

        try {
            DB::select('SELECT 1');
            $db = true;
        } catch (Throwable) {
        }

        try {
            Redis::connection()->ping();
            $redis = true;
        } catch (Throwable) {
        }

        return response()->json([
            'status' => ($db && $redis) ? 'healthy' : 'degraded',
            'services' => compact('db', 'redis'),
            'app' => config('app.env'),
        ]);
    });

    // ── Autenticação ──────────────────────────────────────────────────────────
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:3,60');

    Route::middleware('auth:sanctum')->group(function () {

        // ── Contatos ──────────────────────────────────────────────────────────
        Route::apiResource('contacts', ContactController::class);
        Route::post('contacts/import', [ContactController::class, 'import']);

        // ── Campanhas ─────────────────────────────────────────────────────────
        Route::apiResource('campaigns', CampaignController::class);
        Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])
            ->middleware('throttle:10,1');
        Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])
            ->middleware('throttle:10,1');

        // ── Relatórios ────────────────────────────────────────────────────────
        Route::get('campaigns/{campaign}/report', [ReportController::class, 'campaign']);
        Route::get('dashboard', [ReportController::class, 'dashboard']);

        // ── WhatsApp / Instância ──────────────────────────────────────────────
        Route::get('whatsapp/status', [WhatsAppController::class, 'status']);
        Route::get('whatsapp/qrcode', [WhatsAppController::class, 'qrcode']);
        Route::post('whatsapp/disconnect', [WhatsAppController::class, 'disconnect']);

        // ── Assinatura ────────────────────────────────────────────────────────
        Route::get('subscription', [SubscriptionController::class, 'show']);
        Route::post('subscription/upgrade', [SubscriptionController::class, 'upgrade']);
        Route::post('subscription/cancel', [SubscriptionController::class, 'cancel']);
    });

    // ── Webhook da Evolution API (sem autenticação JWT, usa API key no header) ─
    Route::post('webhooks/evolution', [WebhookController::class, 'evolution'])
        ->middleware([VerifyEvolutionWebhook::class, 'log.webhook:evolution']);

    // ── Webhook do Stripe (sem autenticação Sanctum, usa assinatura do Stripe) ─
    Route::post('webhooks/stripe', [WebhookController::class, 'stripe'])
        ->middleware('log.webhook:stripe');
});

<?php

use App\Http\Controllers\WhatsTrigger\WebController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WhatsTrigger — Web Routes
|--------------------------------------------------------------------------
| Prefixo: /whatstrigger  |  Nome: wt.*
| Autenticação: sessão Laravel (guard web)
*/

Route::get('/', fn () => redirect()->route('wt.dashboard'));

Route::prefix('whatstrigger')->name('wt.')->group(function () {

    // ── Autenticação (guest) ──────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('login', [WebController::class, 'loginForm'])->name('login');
        Route::post('login', [WebController::class, 'login'])->name('login.submit')
            ->middleware('throttle:10,1');
        Route::get('register', [WebController::class, 'registerForm'])->name('register');
        Route::post('register', [WebController::class, 'register'])->name('register.submit')
            ->middleware('throttle:3,60');
    });

    // Logout (não precisa de middleware guest)
    Route::post('logout', [WebController::class, 'logout'])->name('logout');

    // ── Área autenticada ──────────────────────────────────────────────────────
    Route::middleware('auth')->group(function () {

        // Dashboard
        Route::get('/', [WebController::class, 'dashboard'])->name('dashboard');

        // Contatos
        Route::get('contacts', [WebController::class, 'contactsIndex'])->name('contacts.index');
        Route::get('contacts/create', [WebController::class, 'contactsCreate'])->name('contacts.create');
        Route::post('contacts', [WebController::class, 'contactsStore'])->name('contacts.store');
        Route::delete('contacts/{contact}', [WebController::class, 'contactsDestroy'])->name('contacts.destroy');
        Route::get('contacts/export', [WebController::class, 'contactsExport'])->name('contacts.export');

        // Campanhas
        Route::get('campaigns', [WebController::class, 'campaignsIndex'])->name('campaigns.index');
        Route::get('campaigns/create', [WebController::class, 'campaignsCreate'])->name('campaigns.create');
        Route::post('campaigns', [WebController::class, 'campaignsStore'])->name('campaigns.store');
        Route::get('campaigns/{id}', [WebController::class, 'campaignsShow'])->name('campaigns.show');
        Route::post('campaigns/{id}/send', [WebController::class, 'campaignsSend'])->name('campaigns.send');
        Route::post('campaigns/{id}/cancel', [WebController::class, 'campaignsCancel'])->name('campaigns.cancel');

        // WhatsApp
        Route::get('whatsapp', [WebController::class, 'whatsappConnect'])->name('whatsapp.connect');
        Route::get('whatsapp/status', [WebController::class, 'whatsappStatus'])->name('whatsapp.status');
        Route::post('whatsapp/disconnect', [WebController::class, 'whatsappDisconnect'])->name('whatsapp.disconnect');

        // Assinatura
        Route::get('subscription', [WebController::class, 'subscriptionIndex'])->name('subscription.index');

        // Fila
        Route::get('queue/monitor', [WebController::class, 'queueMonitor'])->name('queue.monitor');
        Route::post('queue/failed/{id}/retry', [WebController::class, 'queueFailedRetry'])->name('queue.failed.retry');

        // Webhook Logs
        Route::get('webhooks/logs', [WebController::class, 'webhookLogs'])->name('webhooks.logs');
    });
});

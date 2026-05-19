<?php

use App\Http\Middleware\LogWebhook;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyEvolutionWebhook;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('wt.login'));
        $middleware->alias([
            'verify.evolution' => VerifyEvolutionWebhook::class,
            'log.webhook' => LogWebhook::class,
        ]);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

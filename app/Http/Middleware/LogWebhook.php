<?php

namespace App\Http\Middleware;

use App\Models\WebhookLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogWebhook
{
    public function handle(Request $request, Closure $next, string $provider): Response
    {
        $response = $next($request);

        $content = json_decode($response->getContent(), true);

        WebhookLog::create([
            'provider' => $provider,
            'event' => $request->input('event', $request->header('X-Event-Name')),
            'status' => $response->isSuccessful() ? 'success' : 'failed',
            'payload' => $request->except([]),
            'response' => $content ?: ['raw' => substr($response->getContent(), 0, 1000)],
            'ip_address' => $request->ip(),
        ]);

        return $response;
    }
}

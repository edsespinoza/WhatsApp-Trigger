<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Registrar em bootstrap/app.php (Laravel 11+):
//   ->withMiddleware(function (Middleware $middleware) {
//       $middleware->alias(['verify.evolution' => VerifyEvolutionWebhook::class]);
//   })
class VerifyEvolutionWebhook
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('whatstrigger.evolution.key');
        $received = $request->header('apikey') ?? $request->header('Authorization');

        if (! $expected || ! hash_equals($expected, (string) $received)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}

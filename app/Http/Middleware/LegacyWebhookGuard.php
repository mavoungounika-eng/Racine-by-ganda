<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LegacyWebhookGuard
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $legacyWebhooksEnabled = config('payments.legacy_webhooks_enabled', true);

        $context = [
            'provider' => 'stripe',
            'route_name' => optional($request->route())->getName(),
            'path' => $request->path(),
            'full_url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        if (!$legacyWebhooksEnabled) {
            Log::warning('Legacy webhook blocked', $context + [
                'action' => 'blocked',
                'reason' => 'disabled_by_config',
            ]);

            return response()->json(['error' => 'Legacy webhook disabled'], 410);
        }

        Log::warning('Legacy webhook used', $context + [
            'action' => 'allowed',
        ]);

        return $next($request);
    }
}


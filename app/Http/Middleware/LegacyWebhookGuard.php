<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Legacy Webhook Guard Middleware
 * 
 * Guards legacy webhook endpoints that are deprecated.
 * Behavior is controlled by config('payments.legacy_webhooks_enabled').
 * 
 * @deprecated These routes should be migrated to /api/webhooks/*
 */
class LegacyWebhookGuard
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = (bool) config('payments.legacy_webhooks_enabled', true);

        // Log the attempt for monitoring
        Log::warning('[DEPRECATED] Legacy webhook endpoint accessed', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'enabled' => $enabled,
        ]);

        // If legacy endpoints are disabled, return 410 Gone (config-driven).
        if (!$enabled) {
            abort(410, 'Legacy webhook endpoint disabled. Use /api/webhooks/* instead.');
        }

        return $next($request);
    }
}

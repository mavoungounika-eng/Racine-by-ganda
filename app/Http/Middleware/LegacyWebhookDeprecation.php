<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

/**
 * Legacy Webhook Deprecation Middleware
 * 
 * Logs deprecation warnings for legacy webhook endpoints.
 * Used in combination with LegacyWebhookGuard.
 * 
 * @deprecated These routes should be migrated to /api/webhooks/*
 */
class LegacyWebhookDeprecation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log deprecation warning
        Log::notice('[DEPRECATION] Legacy webhook endpoint used', [
            'path' => $request->path(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour ajouter les headers de dépréciation aux routes legacy webhooks
 */
class LegacyWebhookDeprecationHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Appeler le controller/route suivant
        $response = $next($request);

        // Ajouter les headers de dépréciation pour toutes les réponses (même erreurs 400/401/500)
        // car le middleware doit toujours signaler que l'endpoint est déprécié
        $response->headers->set('Deprecation', 'true');
        $response->headers->set('Sunset', now()->addMonths(6)->toRfc7231String());
        
        // Déterminer l'endpoint officiel selon la route
        $officialEndpoint = '/api/webhooks/stripe';
        if (str_contains($request->path(), 'monetbil')) {
            $officialEndpoint = '/api/webhooks/monetbil';
        }
        $response->headers->set('Link', '<' . url($officialEndpoint) . '>; rel="successor-version"', false);

        // Logger l'usage (sans payload/secrets) uniquement pour les réponses non-erreur
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 400) {
            Log::warning('Legacy webhook endpoint used', [
                'route' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        return $response;
    }
}





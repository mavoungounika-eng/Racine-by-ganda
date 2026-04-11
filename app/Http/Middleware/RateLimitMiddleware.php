<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de rate limiting personnalisé
 * 
 * Permet de définir des limites de taux différenciées par rôle utilisateur
 */
class RateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $decayMinutes Période en minutes
     * @return Response
     */
    public function handle(Request $request, Closure $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Résoudre la signature de la requête pour le rate limiting
     */
    protected function resolveRequestSignature(Request $request): string
    {
        if ($user = $request->user()) {
            return sha1($user->getAuthIdentifier());
        }

        return $request->ip();
    }

    /**
     * Créer une réponse pour trop de tentatives
     */
    protected function buildResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'error' => 'Trop de tentatives. Veuillez réessayer dans ' . ceil($retryAfter / 60) . ' minute(s).',
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
        ]);
    }

    /**
     * Ajouter les headers de rate limiting à la réponse
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ]);

        return $response;
    }

    /**
     * Calculer le nombre de tentatives restantes
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $maxAttempts - RateLimiter::attempts($key);
    }
}


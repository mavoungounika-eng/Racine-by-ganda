<?php

namespace App\Exceptions;

use Exception;

/**
 * CircuitBreakerOpenException
 * 
 * Exception levée quand un circuit breaker est ouvert.
 * Indique que le système refuse temporairement les requêtes
 * pour protéger contre la surcharge.
 */
class CircuitBreakerOpenException extends Exception
{
    public function __construct(string $message = "Circuit breaker is open", int $code = 503)
    {
        parent::__construct($message, $code);
    }

    /**
     * Rapport pour logging
     */
    public function report(): bool
    {
        // Ne pas logger comme erreur critique
        // C'est un comportement normal du circuit breaker
        return false;
    }

    /**
     * Rendu HTTP
     */
    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Service Temporarily Unavailable',
                'message' => $this->getMessage(),
                'code' => 'CIRCUIT_BREAKER_OPEN',
            ], 503);
        }

        return response()->view('errors.503', [
            'message' => 'Le service est temporairement indisponible. Veuillez réessayer dans quelques instants.',
        ], 503);
    }
}

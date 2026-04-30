<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * AssignRequestId Middleware
 * 
 * Génère un ID unique pour chaque requête HTTP.
 * Cet ID est injecté dans les headers de la réponse et utilisé pour corréler
 * les logs d'audit, les logs applicatifs et les traces de performance.
 */
class AssignRequestId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Utiliser l'id existant s'il vient d'un proxy/load-balancer, sinon en générer un
        $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();

        // Injecter dans la requête pour accès facile via $request->id() ou request()->id
        $request->merge(['_request_id' => $requestId]);
        
        // Exécuter la requête
        $response = $next($request);

        // Injecter dans les headers de la réponse pour le client/monitoring
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}

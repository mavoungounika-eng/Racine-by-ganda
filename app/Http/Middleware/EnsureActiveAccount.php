<?php

namespace App\Http\Middleware;

use App\Services\Auth\UserContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function __construct(
        private UserContextResolver $contextResolver
    ) {}

    /**
     * Handle an incoming request.
     * 
     * Verifies that:
     * 1. User has an activeCreatorId in their context.
     * 2. (Optional) The activeCreatorId matches the one required by the route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->contextResolver->getFromSession() 
                 ?? $this->contextResolver->resolve($request->user());

        if (!$context->activeCreatorId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No active creator organization selected.'], 403);
            }
            return redirect()->route('auth.account.list')
                ->with('error', 'Veuillez sélectionner une organisation pour continuer.');
        }

        // Si la route spécifie un {creatorId}, on vérifie la correspondance
        $routeCreatorId = $request->route('creatorId');
        if ($routeCreatorId && (int)$routeCreatorId !== $context->activeCreatorId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Cross-organization access forbidden.'], 403);
            }
            return abort(403, 'Accès inter-organisation refusé.');
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            abort(401, 'Non authentifié');
        }

        $user = auth()->user();

        // Récupérer le slug du rôle
        $roleSlug = $user->getRoleSlug();
        
        // Vérifier si l'utilisateur a un rôle
        if (!$roleSlug) {
            abort(403, 'Aucun rôle assigné');
        }

        // Vérifier si le rôle de l'utilisateur est dans la liste des rôles autorisés
        if (!in_array($roleSlug, $roles)) {
            abort(403, 'Accès refusé. Rôle requis: ' . implode(', ', $roles));
        }

        return $next($request);
    }
}

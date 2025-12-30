<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!auth()->check()) {
            abort(401, 'Non authentifié');
        }

        // Vérifier la permission via Gate
        if (!Gate::allows($permission)) {
            abort(403, "Vous n'avez pas la permission: {$permission}");
        }

        return $next($request);
    }
}

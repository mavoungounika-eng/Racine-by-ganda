<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();
        $user->load('roleRelation');

        // Vérifier si l'utilisateur est admin ou super_admin
        $roleSlug = $user->getRoleSlug();
        
        if (!in_array($roleSlug, ['admin', 'super_admin'])) {
            abort(403, 'Accès administrateur requis.');
        }

        return $next($request);
    }
}

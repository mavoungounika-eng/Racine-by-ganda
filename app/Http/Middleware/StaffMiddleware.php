<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Vérifie que l'utilisateur est connecté et a le rôle 'staff', 'admin' ou 'super_admin'.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();
        $user->load('roleRelation');
        
        $roleSlug = $user->getRoleSlug();

        // Autoriser staff, admin et super_admin
        if (!in_array($roleSlug, ['staff', 'admin', 'super_admin'])) {
            abort(403, 'Accès réservé au personnel.');
        }

        return $next($request);
    }
}


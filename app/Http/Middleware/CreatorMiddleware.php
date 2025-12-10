<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CreatorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'Vous devez être connecté pour accéder à cette page.');
        }

        $user = Auth::user();
        $user->load('roleRelation');
        
        // Vérifier si l'utilisateur a le rôle créateur
        $roleSlug = $user->getRoleSlug();
        
        if (!in_array($roleSlug, ['createur', 'creator'])) {
            abort(403, 'Accès réservé aux créateurs.');
        }

        // Vérifier si l'utilisateur a un profil créateur (optionnel pour l'instant)
        // if (!$user->creatorProfile) {
        //     return redirect()->route('creator.register')
        //         ->with('info', 'Veuillez compléter votre profil créateur.');
        // }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCreatorActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('creator.login');
        }

        $creatorProfile = $user->creatorProfile;

        if (!$creatorProfile) {
            return redirect()->route('creator.register')
                ->with('info', 'Veuillez compléter votre profil créateur.');
        }

        // Vérifier le statut
        if ($creatorProfile->isPending()) {
            return redirect()->route('creator.pending')
                ->with('status', 'Votre compte créateur est en attente de validation par l\'équipe RACINE.');
        }

        if ($creatorProfile->isSuspended()) {
            return redirect()->route('creator.suspended')
                ->with('error', 'Votre compte créateur a été suspendu. Veuillez contacter le support.');
        }

        // Statut actif, continuer
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Services\Auth\PostLoginDecisionEngine;
use App\Services\Auth\UserContextResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function __construct(
        private UserContextResolver $contextResolver,
        private PostLoginDecisionEngine $decisionEngine
    ) {}

    /**
     * Handle an incoming request.
     *
     * Si l'utilisateur est déjà connecté, le rediriger vers son dashboard
     * au lieu de lui montrer la page de connexion.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si utilisateur connecté
        if (Auth::check()) {
            // Résoudre contexte utilisateur
            $context = $this->contextResolver->resolve(Auth::user());
            
            // Déterminer redirection via PostLoginDecisionEngine
            $intended = session()->pull('url.intended');
            $redirectUrl = $this->decisionEngine->determineRedirect($context, $intended);
            
            // Rediriger vers dashboard approprié (ou URL intentionnelle)
            return redirect()->intended($redirectUrl);
        }

        // Sinon, continuer vers page login
        return $next($request);
    }
}

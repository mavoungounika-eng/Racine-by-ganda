<?php

namespace App\Http\Controllers\Auth\Traits;

use App\Models\User;

/**
 * Trait pour gérer les redirections d'authentification selon le rôle
 * 
 * Centralise la logique de redirection pour éviter la duplication
 * entre AuthHubController et LoginController.
 */
trait HandlesAuthRedirect
{
    /**
     * Obtenir le chemin de redirection selon le rôle de l'utilisateur
     * 
     * Gère les redirections intelligentes selon le rôle et le statut créateur :
     * - Client → dashboard client
     * - Créateur (pending) → page pending avec message
     * - Créateur (suspended) → page suspended avec message
     * - Créateur (active) → dashboard créateur
     * 
     * @param User $user
     * @return string
     */
    protected function getRedirectPath(User $user): string
    {
        // 1. Résoudre le contexte utilisateur (Single Source of Truth)
        $resolver = app(\App\Services\Auth\UserContextResolver::class);
        $context = $resolver->resolve($user);

        // 2. Déléguer la décision de redirection au moteur dédié
        $intended = session()->pull('url.intended');
        $engine = app(\App\Services\Auth\PostLoginDecisionEngine::class);
        return $engine->determineRedirect($context, $intended);
    }
}

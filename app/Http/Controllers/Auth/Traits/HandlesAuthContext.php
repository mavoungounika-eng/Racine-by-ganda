<?php

namespace App\Http\Controllers\Auth\Traits;

use Illuminate\Http\Request;

/**
 * Trait pour gérer le contexte d'authentification (boutique/equipe)
 * 
 * Centralise la logique de résolution du contexte pour éviter la duplication
 * entre LoginController et PublicAuthController.
 */
trait HandlesAuthContext
{
    /**
     * Résout le contexte d'authentification depuis la requête et la session
     * 
     * Priorité :
     * 1. Paramètre query `context` si présent et valide
     * 2. Session `{type}_context` si présente et valide
     * 3. null (contexte neutre)
     * 
     * @param Request $request
     * @param string $type Type de contexte ('login', 'register', etc.)
     * @return string|null Retourne 'boutique', 'equipe' ou null
     */
    protected function resolveContext(Request $request, string $type = 'login'): ?string
    {
        $sessionKey = "{$type}_context";
        
        // Priorité 1: Paramètre query si présent et valide
        $queryContext = $request->query('context');
        
        if ($queryContext && in_array($queryContext, ['boutique', 'equipe'], true)) {
            // Stocker en session pour persistance
            session([$sessionKey => $queryContext]);
            return $queryContext;
        }

        // Priorité 2: Session si présente et valide
        $sessionContext = session($sessionKey);
        
        if ($sessionContext && in_array($sessionContext, ['boutique', 'equipe'], true)) {
            return $sessionContext;
        }

        // Nettoyer la session si contexte invalide
        session()->forget($sessionKey);

        // Priorité 3: Contexte neutre
        return null;
    }

    /**
     * Nettoie le contexte de la session après utilisation
     * 
     * @param string $type Type de contexte ('login', 'register', etc.)
     * @return void
     */
    protected function clearContext(string $type = 'login'): void
    {
        session()->forget("{$type}_context");
    }
}

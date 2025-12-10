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
     * @param User $user
     * @return string
     */
    protected function getRedirectPath(User $user): string
    {
        // Charger la relation roleRelation si pas déjà chargée
        if (!$user->relationLoaded('roleRelation')) {
            $user->load('roleRelation');
        }

        $roleSlug = $user->getRoleSlug() ?? 'client';

        return match($roleSlug) {
            'client' => route('account.dashboard'),
            'createur', 'creator' => route('creator.dashboard'),
            'staff' => route('staff.dashboard'),
            'admin', 'super_admin' => route('admin.dashboard'),
            default => route('frontend.home'),
        };
    }
}


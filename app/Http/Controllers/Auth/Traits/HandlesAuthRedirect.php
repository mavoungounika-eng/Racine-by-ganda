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
        // Charger la relation roleRelation si pas déjà chargée
        if (!$user->relationLoaded('roleRelation')) {
            $user->load('roleRelation');
        }

        $roleSlug = $user->getRoleSlug() ?? 'client';

        // Cas spécial : Créateur avec gestion des statuts
        if (in_array($roleSlug, ['createur', 'creator'])) {
            $creatorProfile = $user->creatorProfile;
            
            if (!$creatorProfile) {
                // Pas de profil créateur → rediriger vers onboarding si existe, sinon register
                return \Route::has('creator.onboarding') 
                    ? route('creator.onboarding') 
                    : route('creator.register');
            }
            
            // Gérer les différents statuts du créateur
            switch ($creatorProfile->status) {
                case 'pending':
                    // En attente de validation
                    return route('creator.pending');
                    
                case 'suspended':
                    // Suspendu
                    return route('creator.suspended');
                    
                case 'active':
                    // Actif → dashboard créateur
                    return route('creator.dashboard');
                    
                case 'draft':
                default:
                    // Draft ou statut inconnu → onboarding
                    return \Route::has('creator.onboarding') 
                        ? route('creator.onboarding') 
                        : route('creator.register');
            }
        }

        // Cas par défaut selon le rôle
        return match($roleSlug) {
            'client' => route('account.dashboard'),
            'staff' => route('staff.dashboard'),
            'admin', 'super_admin' => route('admin.dashboard'),
            default => route('frontend.home'),
        };
    }
}


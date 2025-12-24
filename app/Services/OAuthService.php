<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\OauthAccount;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Service centralisé pour gérer l'authentification OAuth
 * 
 * Extrait la logique métier des contrôleurs OAuth pour améliorer
 * la maintenabilité et éviter la duplication de code.
 */
class OAuthService
{
    /**
     * Trouve ou crée un utilisateur à partir des données OAuth
     */
    public function findOrCreateUser(
        SocialiteUser $socialiteUser,
        string $provider,
        string $role = 'client'
    ): User {
        // Rechercher un utilisateur existant par email
        $user = User::where('email', $socialiteUser->getEmail())->first();

        if (!$user) {
            // Créer un nouvel utilisateur
            $user = $this->createUserFromOAuth($socialiteUser, $role);
        }

        // Créer ou mettre à jour le compte OAuth
        $this->syncOAuthAccount($user, $socialiteUser, $provider);

        return $user;
    }

    /**
     * Crée un nouvel utilisateur à partir des données OAuth
     */
    private function createUserFromOAuth(SocialiteUser $socialiteUser, string $role): User
    {
        // Mapper le rôle demandé au slug dans la base
        $roleSlug = $role === 'creator' ? 'createur' : 'client';
        $roleName = $role === 'creator' ? 'Créateur' : 'Client';

        // Récupérer ou créer le rôle
        $roleModel = Role::firstOrCreate(
            ['slug' => $roleSlug],
            [
                'name' => $roleName,
                'description' => $roleName,
                'is_active' => true,
            ]
        );

        // Créer l'utilisateur
        return User::create([
            'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'Utilisateur',
            'email' => $socialiteUser->getEmail(),
            'role_id' => $roleModel->id,
            'email_verified_at' => now(), // OAuth = email vérifié
            'password' => null, // Pas de mot de passe pour OAuth
        ]);
    }

    /**
     * Synchronise le compte OAuth de l'utilisateur
     */
    private function syncOAuthAccount(User $user, SocialiteUser $socialiteUser, string $provider): void
    {
        OauthAccount::updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => $provider,
            ],
            [
                'provider_user_id' => $socialiteUser->getId(),
                'provider_token' => $socialiteUser->token,
                'provider_refresh_token' => $socialiteUser->refreshToken,
                'provider_avatar' => $socialiteUser->getAvatar(),
                'is_primary' => !$user->oauthAccounts()->exists(), // Premier compte = primaire
            ]
        );
    }

    /**
     * Vérifie si un utilisateur existe déjà avec cet email
     */
    public function userExistsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Récupère un utilisateur par son compte OAuth
     */
    public function getUserByOAuthAccount(string $provider, string $providerId): ?User
    {
        $oauthAccount = OauthAccount::where('provider', $provider)
            ->where('provider_user_id', $providerId)
            ->first();

        return $oauthAccount?->user;
    }
}

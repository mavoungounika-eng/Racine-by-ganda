<?php

namespace App\Http\Controllers\Auth\Traits;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

trait HandlesTwoFactor
{
    /**
     * Vérifie si l'utilisateur doit passer par le 2FA et gère la redirection
     */
    protected function handleTwoFactorChallenge(User $user, bool $remember = false): ?RedirectResponse
    {
        $twoFactorService = app(TwoFactorService::class);
        
        // Si 2FA n'est pas activé
        if (!$twoFactorService->isEnabled($user)) {
            // Si 2FA obligatoire mais pas activé
            if ($twoFactorService->isRequired($user)) {
                Auth::login($user, $remember);
                return redirect()->route('2fa.setup')
                    ->with('warning', 'La double authentification est obligatoire pour votre compte.');
            }
            return null; // Pas de 2FA requis
        }
        
        // Vérifier si l'appareil est de confiance
        $trustedToken = request()->cookie('trusted_device');
        if ($trustedToken && $twoFactorService->isTrustedDevice($user, $trustedToken)) {
            return null; // Appareil de confiance, pas de challenge
        }
        
        // Stocker les infos pour le challenge 2FA
        Session::put('2fa_user_id', $user->id);
        Session::put('2fa_remember', $remember);
        
        // Ne pas connecter l'utilisateur, rediriger vers le challenge
        return redirect()->route('2fa.challenge');
    }
    
    /**
     * Complète la connexion après validation 2FA ou sans 2FA
     */
    protected function completeLogin(User $user, bool $remember = false): RedirectResponse
    {
        Auth::login($user, $remember);
        Session::put('2fa_verified', true);
        
        return $this->redirectAfterLogin($user);
    }
    
    /**
     * Redirige l'utilisateur après connexion selon son rôle
     */
    protected function redirectAfterLogin(User $user): RedirectResponse
    {
        $role = $user->getRoleSlug() ?? 'client';
        
        return match($role) {
            'super_admin' => redirect()->route('admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'staff' => redirect()->route('staff.dashboard'),
            'createur', 'creator' => redirect()->route('creator.dashboard'),
            'client' => redirect()->route('account.dashboard'),
            default => redirect()->route('frontend.home'),
        };
    }
}


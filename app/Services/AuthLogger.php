<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service de logging pour les événements d'authentification
 * 
 * Trace tous les événements de sécurité critiques :
 * - Tentatives de connexion (succès/échec)
 * - Changements 2FA
 * - Connexions OAuth
 * - Changements de rôle
 * - Vérifications email
 */
class AuthLogger
{
    /**
     * Log une tentative de connexion
     * PHASE 2 : Événement typé pour meilleure traçabilité
     */
    public function logLoginAttempt(string $email, bool $success, ?string $ip = null): void
    {
        $eventType = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        
        Log::channel('auth')->info($eventType, [
            'email' => $email,
            'success' => $success,
            'ip' => $ip ?? request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log un changement de configuration 2FA
     */
    public function logTwoFactorChange(User $user, string $action): void
    {
        Log::channel('auth')->warning('2FA changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'action' => $action, // 'enabled', 'disabled', 'recovery_codes_regenerated'
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log une connexion OAuth
     */
    public function logOAuthLogin(User $user, string $provider): void
    {
        Log::channel('auth')->info('OAuth login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'provider' => $provider,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log un changement de rôle
     */
    public function logRoleChange(User $user, string $oldRole, string $newRole, ?User $changedBy = null): void
    {
        Log::channel('auth')->warning('Role changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'changed_by' => $changedBy ? $changedBy->id : null,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log une vérification d'email
     */
    public function logEmailVerification(User $user): void
    {
        Log::channel('auth')->info('Email verified', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log une déconnexion
     */
    public function logLogout(User $user): void
    {
        Log::channel('auth')->info('User logout', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log un blocage de compte
     * PHASE 2 : Événement typé ACCOUNT_LOCKED
     */
    public function logAccountLocked(string $email, int $attempts): void
    {
        Log::channel('auth')->warning('ACCOUNT_LOCKED', [
            'email' => $email,
            'failed_attempts' => $attempts,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Log un déclenchement de CAPTCHA
     * PHASE 2 : Nouveau type d'événement
     */
    public function logCaptchaTriggered(string $email, int $attempts): void
    {
        Log::channel('auth')->info('CAPTCHA_TRIGGERED', [
            'email' => $email,
            'failed_attempts' => $attempts,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Log une alerte de sécurité envoyée
     * PHASE 2 : Nouveau type d'événement
     */
    public function logSecurityAlertSent(string $email, string $alertType, array $context = []): void
    {
        Log::channel('auth')->warning('SECURITY_ALERT_SENT', [
            'email' => $email,
            'alert_type' => $alertType,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            ...$context
        ]);
    }

    /**
     * Log une réinitialisation de mot de passe
     */
    public function logPasswordReset(string $email): void
    {
        Log::channel('auth')->info('Password reset', [
            'email' => $email,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}

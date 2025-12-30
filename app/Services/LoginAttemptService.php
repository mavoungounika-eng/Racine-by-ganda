<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Service de gestion des tentatives de connexion échouées
 * 
 * Bloque temporairement les comptes après un nombre défini de tentatives échouées
 * pour protéger contre les attaques par force brute.
 */
class LoginAttemptService
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    /**
     * Enregistre une tentative de connexion échouée
     */
    public function recordFailedAttempt(string $email): void
    {
        $key = $this->getCacheKey($email);
        $attempts = Cache::get($key, 0) + 1;
        
        Cache::put($key, $attempts, now()->addMinutes(self::LOCKOUT_MINUTES));
        
        // Enregistrer l'heure d'expiration pour calculer le temps restant
        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::put($key . ':expires_at', now()->addMinutes(self::LOCKOUT_MINUTES), now()->addMinutes(self::LOCKOUT_MINUTES));
        }
    }

    /**
     * Vérifie si un compte est bloqué
     */
    public function isLocked(string $email): bool
    {
        return Cache::get($this->getCacheKey($email), 0) >= self::MAX_ATTEMPTS;
    }

    /**
     * Efface les tentatives échouées après une connexion réussie
     */
    public function clearAttempts(string $email): void
    {
        $key = $this->getCacheKey($email);
        Cache::forget($key);
        Cache::forget($key . ':expires_at');
    }

    /**
     * Obtient le nombre de minutes restantes avant déblocage
     */
    public function getRemainingMinutes(string $email): int
    {
        $key = $this->getCacheKey($email);
        $expiresAt = Cache::get($key . ':expires_at');
        
        if (!$expiresAt) {
            return 0;
        }
        
        return max(0, now()->diffInMinutes($expiresAt, false));
    }

    /**
     * Obtient le nombre de tentatives échouées
     */
    public function getAttempts(string $email): int
    {
        return Cache::get($this->getCacheKey($email), 0);
    }

    /**
     * Génère la clé de cache pour un email
     */
    private function getCacheKey(string $email): string
    {
        return 'login_attempts:' . md5(strtolower($email));
    }
}

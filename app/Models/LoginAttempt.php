<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'success',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'success' => 'boolean',
    ];

    /**
     * Vérifier si une adresse IP ou un email est bloqué
     */
    public static function isBlocked(string $email, string $ipAddress): bool
    {
        $maxAttempts = 5;
        $lockoutDuration = 30; // minutes

        // Vérifier par email
        $emailKey = "login_attempts_email_{$email}";
        $emailAttempts = Cache::get($emailKey, 0);
        
        if ($emailAttempts >= $maxAttempts) {
            return true;
        }

        // Vérifier par IP
        $ipKey = "login_attempts_ip_{$ipAddress}";
        $ipAttempts = Cache::get($ipKey, 0);
        
        if ($ipAttempts >= $maxAttempts) {
            return true;
        }

        return false;
    }

    /**
     * Enregistrer une tentative de connexion
     */
    public static function record(string $email, string $ipAddress, string $userAgent, bool $success): void
    {
        // Enregistrer en base
        static::create([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => $success,
            'attempted_at' => now(),
        ]);

        $maxAttempts = 5;
        $lockoutDuration = 30; // minutes

        if (!$success) {
            // Incrémenter les tentatives échouées
            $emailKey = "login_attempts_email_{$email}";
            $ipKey = "login_attempts_ip_{$ipAddress}";

            $emailAttempts = Cache::increment($emailKey);
            $ipAttempts = Cache::increment($ipKey);

            // Définir l'expiration
            if ($emailAttempts === 1) {
                Cache::put($emailKey, 1, now()->addMinutes($lockoutDuration));
            }
            if ($ipAttempts === 1) {
                Cache::put($ipKey, 1, now()->addMinutes($lockoutDuration));
            }

            // Logger les tentatives suspectes
            if ($emailAttempts >= 3 || $ipAttempts >= 3) {
                \Log::channel('security')->warning('Tentatives de connexion suspectes', [
                    'email' => $email,
                    'ip' => $ipAddress,
                    'email_attempts' => $emailAttempts,
                    'ip_attempts' => $ipAttempts,
                ]);
            }
        } else {
            // Réinitialiser les compteurs en cas de succès
            Cache::forget("login_attempts_email_{$email}");
            Cache::forget("login_attempts_ip_{$ipAddress}");
        }
    }

    /**
     * Obtenir le temps restant avant déblocage
     */
    public static function getRemainingLockoutTime(string $email, string $ipAddress): ?int
    {
        $emailKey = "login_attempts_email_{$email}";
        $ipKey = "login_attempts_ip_{$ipAddress}";

        $emailAttempts = Cache::get($emailKey, 0);
        $ipAttempts = Cache::get($ipKey, 0);

        if ($emailAttempts >= 5) {
            $ttl = Cache::get("login_attempts_email_{$email}_ttl", 30);
            return $ttl;
        }

        if ($ipAttempts >= 5) {
            $ttl = Cache::get("login_attempts_ip_{$ipAddress}_ttl", 30);
            return $ttl;
        }

        return null;
    }
}


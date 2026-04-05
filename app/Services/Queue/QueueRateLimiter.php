<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * QueueRateLimiter - Limitation du débit des jobs
 * 
 * Implémente rate limiting pour les queues Laravel.
 * Limite le nombre de jobs traités par période (minute/seconde).
 * Utilise Redis pour tracking distribué.
 */
class QueueRateLimiter
{
    protected array $limits;

    public function __construct()
    {
        $this->limits = config('queue-protection.rate_limits');
    }

    /**
     * Tenter d'exécuter un job (rate limiting)
     * 
     * @param string $jobType Type de job (webhooks, emails, etc.)
     * @return bool True si autorisé, false si limite atteinte
     */
    public function attempt(string $jobType): bool
    {
        $limit = $this->getLimit($jobType);
        
        if (!$limit) {
            return true; // Pas de limite configurée
        }
        
        [$maxAttempts, $decaySeconds] = $this->parseLimit($limit);
        
        $key = $this->getKey($jobType);
        $current = (int) Redis::get($key) ?? 0;
        
        if ($current >= $maxAttempts) {
            Log::debug('[RATE LIMITER] Limit reached', [
                'job_type' => $jobType,
                'current' => $current,
                'limit' => $maxAttempts,
            ]);
            
            return false;
        }
        
        // Incrémenter compteur
        $newCount = Redis::incr($key);
        
        // Définir expiration si premier
        if ($newCount === 1) {
            Redis::expire($key, $decaySeconds);
        }
        
        return true;
    }

    /**
     * Nombre de tentatives restantes
     */
    public function remaining(string $jobType): int
    {
        $limit = $this->getLimit($jobType);
        
        if (!$limit) {
            return PHP_INT_MAX;
        }
        
        [$maxAttempts, $decaySeconds] = $this->parseLimit($limit);
        
        $key = $this->getKey($jobType);
        $current = (int) Redis::get($key) ?? 0;
        
        return max(0, $maxAttempts - $current);
    }

    /**
     * Temps avant disponibilité (secondes)
     */
    public function availableIn(string $jobType): int
    {
        $key = $this->getKey($jobType);
        $ttl = Redis::ttl($key);
        
        return $ttl > 0 ? $ttl : 0;
    }

    /**
     * Réinitialiser le compteur
     */
    public function clear(string $jobType): void
    {
        Redis::del($this->getKey($jobType));
    }

    /**
     * Obtenir métriques
     */
    public function getMetrics(string $jobType): array
    {
        $limit = $this->getLimit($jobType);
        
        if (!$limit) {
            return [
                'job_type' => $jobType,
                'limit' => 'unlimited',
                'current' => 0,
                'remaining' => PHP_INT_MAX,
                'available_in' => 0,
            ];
        }
        
        [$maxAttempts, $decaySeconds] = $this->parseLimit($limit);
        $key = $this->getKey($jobType);
        $current = (int) Redis::get($key) ?? 0;
        
        return [
            'job_type' => $jobType,
            'limit' => $limit,
            'max_attempts' => $maxAttempts,
            'decay_seconds' => $decaySeconds,
            'current' => $current,
            'remaining' => max(0, $maxAttempts - $current),
            'available_in' => $this->availableIn($jobType),
        ];
    }

    /**
     * Obtenir la limite pour un type de job
     */
    protected function getLimit(string $jobType): ?string
    {
        return $this->limits[$jobType] ?? $this->limits['default'] ?? null;
    }

    /**
     * Parser format limite (ex: "100/minute" => [100, 60])
     */
    protected function parseLimit(string $limit): array
    {
        [$maxAttempts, $period] = explode('/', $limit);
        
        $decaySeconds = match ($period) {
            'second' => 1,
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            default => 60,
        };
        
        return [(int) $maxAttempts, $decaySeconds];
    }

    /**
     * Clé Redis
     */
    protected function getKey(string $jobType): string
    {
        return "rate_limiter:queue:{$jobType}";
    }

    /**
     * Obtenir tous les types de jobs configurés
     */
    public function getConfiguredJobTypes(): array
    {
        return array_keys($this->limits);
    }

    /**
     * Obtenir métriques pour tous les types
     */
    public function getAllMetrics(): array
    {
        $metrics = [];
        
        foreach ($this->getConfiguredJobTypes() as $jobType) {
            $metrics[$jobType] = $this->getMetrics($jobType);
        }
        
        return $metrics;
    }
}

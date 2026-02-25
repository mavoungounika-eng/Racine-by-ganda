<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * QueueCircuitBreaker - Protection contre surcharge queues
 * 
 * Implémente le pattern Circuit Breaker pour les queues Laravel.
 * Ouvre le circuit après N échecs consécutifs, empêchant le traitement
 * jusqu'à un cooldown. Ferme après M succès consécutifs.
 * 
 * États:
 * - CLOSED: Normal, jobs traités
 * - OPEN: Circuit ouvert, jobs rejetés
 * - HALF_OPEN: Test après cooldown
 */
class QueueCircuitBreaker
{
    const STATE_CLOSED = 'closed';
    const STATE_OPEN = 'open';
    const STATE_HALF_OPEN = 'half_open';

    protected int $failureThreshold;
    protected int $successThreshold;
    protected int $timeout;
    protected int $ttl;

    public function __construct()
    {
        $config = config('queue-protection.circuit_breaker');
        
        $this->failureThreshold = $config['failure_threshold'];
        $this->successThreshold = $config['success_threshold'];
        $this->timeout = $config['timeout'];
        $this->ttl = $config['ttl'];
    }

    /**
     * Vérifier si le circuit est ouvert pour une queue
     */
    public function isOpen(string $queue): bool
    {
        $state = $this->getState($queue);
        
        // Si OPEN, vérifier si cooldown écoulé
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset($queue)) {
                $this->setState($queue, self::STATE_HALF_OPEN);
                return false; // Permettre un test
            }
            return true;
        }
        
        return false;
    }

    /**
     * Enregistrer un succès
     */
    public function recordSuccess(string $queue): void
    {
        $state = $this->getState($queue);
        
        if ($state === self::STATE_HALF_OPEN) {
            // Incrémenter compteur succès
            $successCount = $this->incrementSuccessCount($queue);
            
            if ($successCount >= $this->successThreshold) {
                $this->reset($queue);
                Log::info('[CIRCUIT BREAKER] Circuit closed', [
                    'queue' => $queue,
                    'success_count' => $successCount,
                ]);

                // Notification
                $this->notifyCircuitClosed($queue);
            }
        } elseif ($state === self::STATE_CLOSED) {
            // Reset compteur échecs
            $this->resetFailureCount($queue);
        }
    }

    /**
     * Enregistrer un échec
     */
    public function recordFailure(string $queue): void
    {
        $state = $this->getState($queue);
        
        if ($state === self::STATE_HALF_OPEN) {
            // Incrémenter les tentatives (exponential backoff)
            $retries = $this->incrementRetryCount($queue);
            
            // Retour à OPEN
            $this->setState($queue, self::STATE_OPEN);
            $this->setOpenedAt($queue, now());
            
            Log::warning('[CIRCUIT BREAKER] Circuit re-opened after test failure (Exponential backoff applied)', [
                'queue' => $queue,
                'retry_attempt' => $retries,
                'next_timeout' => $this->calculateTimeout($queue),
            ]);
            
            return;
        }
        
        // Incrémenter compteur échecs
        $failureCount = $this->incrementFailureCount($queue);
        
        if ($failureCount >= $this->failureThreshold) {
            $this->setState($queue, self::STATE_OPEN);
            $this->setOpenedAt($queue, now());
            
            Log::error('[CIRCUIT BREAKER] Circuit opened', [
                'queue' => $queue,
                'failure_count' => $failureCount,
                'threshold' => $this->failureThreshold,
            ]);
            
            // Notification
            $this->notifyCircuitOpened($queue, $failureCount);
        }
    }

    /**
     * Réinitialiser le circuit
     */
    public function reset(string $queue): void
    {
        $this->setState($queue, self::STATE_CLOSED);
        $this->resetFailureCount($queue);
        $this->resetSuccessCount($queue);
        $this->resetRetryCount($queue);
        Redis::del($this->getOpenedAtKey($queue));
    }

    /**
     * Obtenir l'état du circuit
     */
    public function getState(string $queue): string
    {
        return Redis::get($this->getStateKey($queue)) ?? self::STATE_CLOSED;
    }

    /**
     * Obtenir les métriques du circuit
     */
    public function getMetrics(string $queue): array
    {
        return [
            'state' => $this->getState($queue),
            'failure_count' => $this->getFailureCount($queue),
            'success_count' => $this->getSuccessCount($queue),
            'opened_at' => $this->getOpenedAt($queue),
            'failure_threshold' => $this->failureThreshold,
            'success_threshold' => $this->successThreshold,
            'timeout' => $this->timeout,
            'current_timeout' => $this->calculateTimeout($queue),
            'retry_count' => $this->getRetryCount($queue),
        ];
    }

    /**
     * Vérifier si on doit tenter de réinitialiser
     */
    protected function shouldAttemptReset(string $queue): bool
    {
        $openedAt = $this->getOpenedAt($queue);
        
        if (!$openedAt) {
            return true;
        }
        
        $currentTimeout = $this->calculateTimeout($queue);
        
        return Carbon::parse($openedAt)->addSeconds($currentTimeout)->isPast();
    }

    /**
     * Calculer le timeout actuel avec exponential backoff
     */
    protected function calculateTimeout(string $queue): int
    {
        $retries = $this->getRetryCount($queue);
        
        if ($retries <= 0) {
            return $this->timeout;
        }
        
        // Timeout = base_timeout * (2 ^ retries)
        // Max 24 hours to avoid overflow
        $multiplier = pow(2, min($retries, 12)); 
        return min($this->timeout * (int)$multiplier, 24 * 3600);
    }

    /**
     * Définir l'état
     */
    protected function setState(string $queue, string $state): void
    {
        Redis::setex($this->getStateKey($queue), $this->ttl, $state);
    }

    /**
     * Incrémenter compteur échecs
     */
    protected function incrementFailureCount(string $queue): int
    {
        $key = $this->getFailureCountKey($queue);
        $count = Redis::incr($key);
        Redis::expire($key, $this->ttl);
        return $count;
    }

    /**
     * Incrémenter compteur succès
     */
    protected function incrementSuccessCount(string $queue): int
    {
        $key = $this->getSuccessCountKey($queue);
        $count = Redis::incr($key);
        Redis::expire($key, $this->ttl);
        return $count;
    }

    /**
     * Réinitialiser compteur échecs
     */
    protected function resetFailureCount(string $queue): void
    {
        Redis::del($this->getFailureCountKey($queue));
    }

    /**
     * Réinitialiser compteur succès
     */
    protected function resetSuccessCount(string $queue): void
    {
        Redis::del($this->getSuccessCountKey($queue));
    }

    /**
     * Obtenir compteur échecs
     */
    protected function getFailureCount(string $queue): int
    {
        return (int) Redis::get($this->getFailureCountKey($queue)) ?? 0;
    }

    /**
     * Obtenir compteur succès
     */
    protected function getSuccessCount(string $queue): int
    {
        return (int) Redis::get($this->getSuccessCountKey($queue)) ?? 0;
    }

    /**
     * Obtenir compteur tentatives (backoff)
     */
    protected function getRetryCount(string $queue): int
    {
        return (int) Redis::get($this->getRetryCountKey($queue)) ?? 0;
    }

    /**
     * Incrémenter compteur tentatives
     */
    protected function incrementRetryCount(string $queue): int
    {
        $key = $this->getRetryCountKey($queue);
        $count = Redis::incr($key);
        Redis::expire($key, $this->ttl);
        return $count;
    }

    /**
     * Réinitialiser compteur tentatives
     */
    protected function resetRetryCount(string $queue): void
    {
        Redis::del($this->getRetryCountKey($queue));
    }

    /**
     * Définir timestamp ouverture
     */
    protected function setOpenedAt(string $queue, Carbon $timestamp): void
    {
        Redis::setex(
            $this->getOpenedAtKey($queue),
            $this->ttl,
            $timestamp->toIso8601String()
        );
    }

    /**
     * Obtenir timestamp ouverture
     */
    protected function getOpenedAt(string $queue): ?string
    {
        return Redis::get($this->getOpenedAtKey($queue));
    }

    /**
     * Notifier ouverture circuit
     */
    protected function notifyCircuitOpened(string $queue, int $failureCount): void
    {
        // Intégration avec AlertService
        try {
            $alertService = app(\App\Services\Monitoring\AlertService::class);
            
            $alertService->critical(
                "Circuit Breaker Opened - Queue: {$queue}",
                "The circuit breaker has opened for queue '{$queue}' after {$failureCount} consecutive failures. Jobs are being rejected to prevent system overload.",
                [
                    'queue' => $queue,
                    'failure_count' => $failureCount,
                    'failure_threshold' => $this->failureThreshold,
                    'timeout' => $this->timeout,
                    'action_required' => 'Investigate queue failures and reset circuit breaker when resolved',
                ]
            );
        } catch (\Exception $e) {
            Log::error('[CIRCUIT BREAKER] Failed to send alert', [
                'error' => $e->getMessage(),
            ]);
        }
        
        // Fallback log
        Log::critical('[CIRCUIT BREAKER] ALERT: Circuit opened', [
            'queue' => $queue,
            'failure_count' => $failureCount,
            'action_required' => 'Investigate queue failures',
        ]);
    }

    /**
     * Notifier fermeture circuit
     */
    protected function notifyCircuitClosed(string $queue): void
    {
        // Intégration avec AlertService
        try {
            $alertService = app(\App\Services\Monitoring\AlertService::class);
            
            $alertService->info(
                "Circuit Breaker Closed - Queue: {$queue}",
                "The circuit breaker has successfully closed for queue '{$queue}'. Normal processing has resumed.",
                [
                    'queue' => $queue,
                    'status' => 'recovered',
                ]
            );
        } catch (\Exception $e) {
            Log::error('[CIRCUIT BREAKER] Failed to send recovery alert', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clés Redis
     */
    protected function getStateKey(string $queue): string
    {
        return "circuit_breaker:{$queue}:state";
    }

    protected function getFailureCountKey(string $queue): string
    {
        return "circuit_breaker:{$queue}:failures";
    }

    protected function getSuccessCountKey(string $queue): string
    {
        return "circuit_breaker:{$queue}:successes";
    }

    protected function getOpenedAtKey(string $queue): string
    {
        return "circuit_breaker:{$queue}:opened_at";
    }

    protected function getRetryCountKey(string $queue): string
    {
        return "circuit_breaker:{$queue}:retries";
    }
}

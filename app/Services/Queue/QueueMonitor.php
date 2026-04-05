<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

/**
 * QueueMonitor - Monitoring et métriques des queues
 * 
 * Collecte métriques pour:
 * - Taille des queues
 * - Temps de traitement
 * - Taux d'échec
 * - État circuit breaker
 * 
 * Expose métriques pour Prometheus/Grafana
 */
class QueueMonitor
{
    protected QueueCircuitBreaker $circuitBreaker;
    protected QueueRateLimiter $rateLimiter;

    public function __construct(
        QueueCircuitBreaker $circuitBreaker,
        QueueRateLimiter $rateLimiter
    ) {
        $this->circuitBreaker = $circuitBreaker;
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Collecter toutes les métriques
     */
    public function collect(): array
    {
        $queues = $this->getConfiguredQueues();
        $metrics = [];

        foreach ($queues as $queue) {
            $metrics[$queue] = [
                'queue_size' => $this->getQueueSize($queue),
                'processing_time' => $this->getProcessingTime($queue),
                'failure_rate' => $this->getFailureRate($queue),
                'circuit_breaker' => $this->circuitBreaker->getMetrics($queue),
                'rate_limiter' => $this->rateLimiter->getMetrics($queue),
                'workers' => $this->getWorkerCount($queue),
            ];
        }

        return $metrics;
    }

    /**
     * Obtenir taille d'une queue
     */
    public function getQueueSize(string $queue): int
    {
        try {
            // Laravel utilise Redis lists pour les queues
            $key = "queues:{$queue}";
            return (int) Redis::llen($key);
        } catch (\Exception $e) {
            Log::error('[QUEUE MONITOR] Error getting queue size', [
                'queue' => $queue,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Obtenir temps de traitement moyen (dernière heure)
     */
    public function getProcessingTime(string $queue): array
    {
        $key = $this->getProcessingTimeKey($queue);
        
        try {
            $times = Redis::lrange($key, 0, -1);
            
            if (empty($times)) {
                return [
                    'avg' => 0,
                    'p50' => 0,
                    'p95' => 0,
                    'p99' => 0,
                ];
            }
            
            $times = array_map('floatval', $times);
            sort($times);
            
            return [
                'avg' => array_sum($times) / count($times),
                'p50' => $this->percentile($times, 50),
                'p95' => $this->percentile($times, 95),
                'p99' => $this->percentile($times, 99),
            ];
        } catch (\Exception $e) {
            Log::error('[QUEUE MONITOR] Error getting processing time', [
                'queue' => $queue,
                'error' => $e->getMessage(),
            ]);
            
            return ['avg' => 0, 'p50' => 0, 'p95' => 0, 'p99' => 0];
        }
    }

    /**
     * Enregistrer temps de traitement
     */
    public function recordProcessingTime(string $queue, float $duration): void
    {
        $key = $this->getProcessingTimeKey($queue);
        
        // Ajouter à la liste
        Redis::rpush($key, $duration);
        
        // Limiter à 1000 entrées
        Redis::ltrim($key, -1000, -1);
        
        // Expiration 1 heure
        Redis::expire($key, 3600);
    }

    /**
     * Obtenir taux d'échec (dernière heure)
     */
    public function getFailureRate(string $queue): float
    {
        $totalKey = $this->getTotalJobsKey($queue);
        $failedKey = $this->getFailedJobsKey($queue);
        
        $total = (int) Redis::get($totalKey) ?? 0;
        $failed = (int) Redis::get($failedKey) ?? 0;
        
        if ($total === 0) {
            return 0.0;
        }
        
        return round(($failed / $total) * 100, 2);
    }

    /**
     * Enregistrer job traité
     */
    public function recordJobProcessed(string $queue, bool $success): void
    {
        $totalKey = $this->getTotalJobsKey($queue);
        Redis::incr($totalKey);
        Redis::expire($totalKey, 3600);
        
        if (!$success) {
            $failedKey = $this->getFailedJobsKey($queue);
            Redis::incr($failedKey);
            Redis::expire($failedKey, 3600);
        }
    }

    /**
     * Obtenir nombre de workers actifs
     */
    public function getWorkerCount(string $queue): int
    {
        // Approximation via Redis
        // Laravel stocke les workers dans des clés temporaires
        try {
            $pattern = "laravel:queue:worker:*:{$queue}";
            $keys = Redis::keys($pattern);
            return count($keys);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Vérifier seuils d'alerte
     */
    public function checkThresholds(): array
    {
        $alerts = [];
        $thresholds = config('queue-protection.monitoring.thresholds');
        $queues = $this->getConfiguredQueues();

        foreach ($queues as $queue) {
            // Taille queue
            $size = $this->getQueueSize($queue);
            if ($size >= $thresholds['queue_size']['critical']) {
                $alerts[] = [
                    'severity' => 'critical',
                    'queue' => $queue,
                    'metric' => 'queue_size',
                    'value' => $size,
                    'threshold' => $thresholds['queue_size']['critical'],
                ];
            } elseif ($size >= $thresholds['queue_size']['warning']) {
                $alerts[] = [
                    'severity' => 'warning',
                    'queue' => $queue,
                    'metric' => 'queue_size',
                    'value' => $size,
                    'threshold' => $thresholds['queue_size']['warning'],
                ];
            }

            // Temps traitement
            $processingTime = $this->getProcessingTime($queue);
            if ($processingTime['p95'] >= $thresholds['processing_time']['critical']) {
                $alerts[] = [
                    'severity' => 'critical',
                    'queue' => $queue,
                    'metric' => 'processing_time_p95',
                    'value' => $processingTime['p95'],
                    'threshold' => $thresholds['processing_time']['critical'],
                ];
            }

            // Taux échec
            $failureRate = $this->getFailureRate($queue);
            if ($failureRate >= $thresholds['failure_rate']['critical'] * 100) {
                $alerts[] = [
                    'severity' => 'critical',
                    'queue' => $queue,
                    'metric' => 'failure_rate',
                    'value' => $failureRate,
                    'threshold' => $thresholds['failure_rate']['critical'] * 100,
                ];
            }
        }

        return $alerts;
    }

    /**
     * Exporter métriques format Prometheus
     */
    public function exportPrometheus(): string
    {
        $metrics = $this->collect();
        $output = [];

        foreach ($metrics as $queue => $data) {
            // Queue size
            $output[] = sprintf(
                'queue_size{queue="%s"} %d',
                $queue,
                $data['queue_size']
            );

            // Processing time
            $output[] = sprintf(
                'queue_processing_time_avg{queue="%s"} %.3f',
                $queue,
                $data['processing_time']['avg']
            );
            $output[] = sprintf(
                'queue_processing_time_p95{queue="%s"} %.3f',
                $queue,
                $data['processing_time']['p95']
            );

            // Failure rate
            $output[] = sprintf(
                'queue_failure_rate{queue="%s"} %.2f',
                $queue,
                $data['failure_rate']
            );

            // Circuit breaker
            $cbState = $data['circuit_breaker']['state'];
            $cbStateValue = match ($cbState) {
                'closed' => 0,
                'half_open' => 1,
                'open' => 2,
                default => -1,
            };
            $output[] = sprintf(
                'circuit_breaker_state{queue="%s"} %d',
                $queue,
                $cbStateValue
            );

            // Rate limiter
            $output[] = sprintf(
                'rate_limiter_remaining{queue="%s"} %d',
                $queue,
                $data['rate_limiter']['remaining']
            );
        }

        return implode("\n", $output) . "\n";
    }

    /**
     * Calculer percentile
     */
    protected function percentile(array $sorted, int $percentile): float
    {
        $index = ceil(count($sorted) * $percentile / 100) - 1;
        return $sorted[$index] ?? 0;
    }

    /**
     * Obtenir queues configurées
     */
    protected function getConfiguredQueues(): array
    {
        return array_keys(config('queue-protection.rate_limits'));
    }

    /**
     * Clés Redis
     */
    protected function getProcessingTimeKey(string $queue): string
    {
        return "queue_monitor:{$queue}:processing_times";
    }

    protected function getTotalJobsKey(string $queue): string
    {
        return "queue_monitor:{$queue}:total_jobs";
    }

    protected function getFailedJobsKey(string $queue): string
    {
        return "queue_monitor:{$queue}:failed_jobs";
    }
}

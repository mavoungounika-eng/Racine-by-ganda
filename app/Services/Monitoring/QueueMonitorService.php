<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * QueueMonitorService - Analyse approfondie des files d'attente
 * 
 * Mesure :
 * - Latence (temps entre push et pop)
 * - Débit (jobs/minute)
 * - Taux d'échec
 * - Occupation (jobs cumulés)
 */
class QueueMonitorService
{
    /**
     * Rapport détaillé sur une queue spécifique
     */
    public function getMetrics(string $queue = 'default'): array
    {
        try {
            $redis = Redis::connection();
            $baseKey = "queues:{$queue}";

            return [
                'queue' => $queue,
                'current_size' => $redis->llen($baseKey),
                'processing_size' => $redis->zcard($baseKey . ':processing'),
                'failed_size' => $redis->zcard($baseKey . ':failed'), // Si Horizon n'est pas utilisé
                'reserved_size' => $redis->zcard($baseKey . ':reserved'),
                'latency' => $this->estimateLatency($queue),
                'throughput' => $this->getThroughput($queue),
            ];
        } catch (Exception $e) {
            Log::error("[QUEUE MONITOR] Failed to fetch metrics for {$queue}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Estimer la latence (temps d'attente du job le plus vieux)
     */
    protected function estimateLatency(string $queue): int
    {
        try {
            $job = Redis::lindex("queues:{$queue}", -1);
            if (!$job) return 0;

            $data = json_decode($job, true);
            $pushedAt = $data['pushed_at'] ?? $data['created_at'] ?? null;

            if (!$pushedAt) return 0;

            return max(0, time() - (int)$pushedAt);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Obtenir le débit approximatif (jobs traités récemment)
     * Note: Nécessite que l'application logue les fins de jobs dans Redis
     */
    protected function getThroughput(string $queue): array
    {
        // On pourrait utiliser Horizon ou un compteur custom
        return [
            'last_hour' => 'N/A (Horizon required for precise metrics)',
        ];
    }

    /**
     * Alerter si la queue dépasse un seuil critique
     */
    public function checkThresholds(string $queue = 'default', int $maxSize = 100, int $maxLatency = 60): array
    {
        $metrics = $this->getMetrics($queue);
        $alerts = [];

        if (($metrics['current_size'] ?? 0) > $maxSize) {
            $alerts[] = [
                'type' => 'queue_size_exceeded',
                'severity' => 'high',
                'message' => "Queue '{$queue}' has {$metrics['current_size']} jobs (threshold: {$maxSize})",
            ];
        }

        if (($metrics['latency'] ?? 0) > $maxLatency) {
            $alerts[] = [
                'type' => 'queue_latency_high',
                'severity' => 'critical',
                'message' => "Queue '{$queue}' latency is {$metrics['latency']}s (threshold: {$maxLatency}s)",
            ];
        }

        return $alerts;
    }
}

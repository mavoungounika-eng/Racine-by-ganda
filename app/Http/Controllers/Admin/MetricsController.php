<?php

namespace App\Http\Controllers\Admin;

use App\Services\Queue\QueueMonitor;
use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Queue\QueueRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * MetricsController - Exposition métriques système
 * 
 * Endpoints:
 * - GET /metrics - Format Prometheus
 * - GET /admin/queue-metrics - Dashboard JSON
 */
class MetricsController
{
    public function __construct(
        protected QueueMonitor $monitor,
        protected QueueCircuitBreaker $circuitBreaker,
        protected QueueRateLimiter $rateLimiter
    ) {}

    /**
     * Métriques Prometheus
     * 
     * @return Response
     */
    public function prometheus(): Response
    {
        $metrics = $this->monitor->exportPrometheus();
        
        return response($metrics, 200, [
            'Content-Type' => 'text/plain; version=0.0.4',
        ]);
    }

    /**
     * Dashboard métriques queues (JSON)
     * 
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class); // Admin only

        $metrics = $this->monitor->collect();
        $alerts = $this->monitor->checkThresholds();

        return response()->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'alerts' => $alerts,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Réinitialiser circuit breaker
     * 
     * @param string $queue
     * @return JsonResponse
     */
    public function resetCircuitBreaker(string $queue): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class); // Admin only

        $this->circuitBreaker->reset($queue);

        return response()->json([
            'success' => true,
            'message' => "Circuit breaker reset for queue: {$queue}",
        ]);
    }

    /**
     * Réinitialiser rate limiter
     * 
     * @param string $jobType
     * @return JsonResponse
     */
    public function resetRateLimiter(string $jobType): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class); // Admin only

        $this->rateLimiter->clear($jobType);

        return response()->json([
            'success' => true,
            'message' => "Rate limiter reset for job type: {$jobType}",
        ]);
    }

    /**
     * Health check
     * 
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        $alerts = $this->monitor->checkThresholds();
        $criticalAlerts = array_filter($alerts, fn($a) => $a['severity'] === 'critical');

        $status = empty($criticalAlerts) ? 'healthy' : 'degraded';
        $httpCode = empty($criticalAlerts) ? 200 : 503;

        return response()->json([
            'status' => $status,
            'alerts' => $alerts,
            'timestamp' => now()->toIso8601String(),
        ], $httpCode);
    }
}

<?php

namespace App\Services\Webhooks;

use App\Models\WebhookHealthCheck;
use App\Models\WebhookMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Observability Service
 * 
 * Provides comprehensive visibility into webhook operations:
 * - Real-time metrics tracking
 * - Performance monitoring
 * - Health status aggregation
 * - Dashboard data generation
 */
class WebhookObservabilityService
{
    /**
     * Record a webhook metric event.
     * Should be called after webhook processing completes.
     */
    public function recordMetric(
        string $provider,
        string $eventType,
        int $responseTimeMs,
        bool $success = true,
        ?string $errorMessage = null,
        ?string $handler = null,
        ?string $webhookId = null,
        int $retryCount = 0,
        ?string $statusCode = null,
        ?string $circuitState = null,
        array $tags = [],
        array $context = []
    ): WebhookMetric {
        return WebhookMetric::create([
            'provider' => $provider,
            'event_type' => $eventType,
            'webhook_id' => $webhookId,
            'response_time_ms' => $responseTimeMs,
            'retry_count' => $retryCount,
            'status_code' => $statusCode,
            'handler' => $handler,
            'success' => $success,
            'error_message' => $errorMessage,
            'received_at' => now(),
            'started_at' => now()->subMilliseconds($responseTimeMs),
            'completed_at' => now(),
            'circuit_state' => $circuitState,
            'tags' => $tags,
            'context' => $context,
        ]);
    }

    /**
     * Get real-time dashboard data.
     */
    public function getDashboardData($timeRangeMinutes = 60): array
    {
        $timeRange = [now()->subMinutes($timeRangeMinutes), now()];

        return [
            'summary' => $this->getSystemSummary($timeRange),
            'providers' => $this->getProviderMetrics($timeRange),
            'recent_events' => $this->getRecentEvents(50),
            'health_status' => WebhookHealthCheck::getOverallHealth(),
            'performance_trends' => $this->getPerformanceTrends($timeRange),
            'error_distribution' => $this->getErrorDistribution($timeRange),
        ];
    }

    /**
     * Get system summary metrics.
     */
    public function getSystemSummary($timeRange = null): array
    {
        $query = WebhookMetric::query();

        if ($timeRange) {
            $query->inTimeRange($timeRange[0], $timeRange[1]);
        }

        $metrics = $query->get();

        $totalEvents = $metrics->count();
        $successfulEvents = $metrics->where('success', true)->count();
        $failedEvents = $metrics->where('success', false)->count();
        $avgResponseTime = $metrics->avg('response_time_ms') ?? 0;

        return [
            'total_events' => $totalEvents,
            'successful_events' => $successfulEvents,
            'failed_events' => $failedEvents,
            'success_rate' => $totalEvents > 0 ? ($successfulEvents / $totalEvents) * 100 : 100,
            'error_rate' => $totalEvents > 0 ? ($failedEvents / $totalEvents) * 100 : 0,
            'avg_response_time_ms' => round($avgResponseTime, 2),
            'p95_response_time_ms' => $this->getPercentileResponseTime($metrics, 95),
            'p99_response_time_ms' => $this->getPercentileResponseTime($metrics, 99),
            'throughput_per_minute' => round($totalEvents / max($timeRange ? $timeRange[0]->diffInMinutes($timeRange[1]) : 1, 1), 2),
        ];
    }

    /**
     * Get per-provider metrics.
     */
    public function getProviderMetrics($timeRange = null): array
    {
        $providers = WebhookMetric::distinct('provider')
            ->pluck('provider')
            ->toArray();

        return array_map(function ($provider) use ($timeRange) {
            return WebhookMetric::getSummary($provider, $timeRange);
        }, $providers);
    }

    /**
     * Get recent webhook events with full details.
     */
    public function getRecentEvents(int $limit = 50): array
    {
        return WebhookMetric::latest()
            ->limit($limit)
            ->get()
            ->map(fn ($metric) => [
                'id' => $metric->id,
                'provider' => $metric->provider,
                'event_type' => $metric->event_type,
                'webhook_id' => $metric->webhook_id,
                'success' => $metric->success,
                'response_time_ms' => $metric->response_time_ms,
                'status_code' => $metric->status_code,
                'error_message' => $metric->error_message,
                'handler' => $metric->handler,
                'circuit_state' => $metric->circuit_state,
                'created_at' => $metric->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Get performance trends over time.
     */
    public function getPerformanceTrends($timeRange = null, int $buckets = 12): array
    {
        if (!$timeRange) {
            $timeRange = [now()->subHours(1), now()];
        }

        $interval = $timeRange[0]->diffInMinutes($timeRange[1]) / $buckets;

        $trends = [];
        for ($i = 0; $i < $buckets; $i++) {
            $bucketStart = $timeRange[0]->copy()->addMinutes($i * $interval);
            $bucketEnd = $bucketStart->copy()->addMinutes($interval);

            $metrics = WebhookMetric::inTimeRange($bucketStart, $bucketEnd)->get();

            $trends[] = [
                'timestamp' => $bucketStart->toIso8601String(),
                'total_events' => $metrics->count(),
                'successful' => $metrics->where('success', true)->count(),
                'failed' => $metrics->where('success', false)->count(),
                'avg_response_time_ms' => round($metrics->avg('response_time_ms') ?? 0, 2),
            ];
        }

        return $trends;
    }

    /**
     * Get error distribution by type.
     */
    public function getErrorDistribution($timeRange = null): array
    {
        $query = WebhookMetric::failed();

        if ($timeRange) {
            $query->inTimeRange($timeRange[0], $timeRange[1]);
        }

        $errors = $query->get()
            ->groupBy('error_message')
            ->map(fn ($group) => [
                'error_message' => $group->first()->error_message,
                'count' => $group->count(),
                'percentage' => round(($group->count() / $query->count()) * 100, 2),
            ])
            ->sortByDesc('count')
            ->values()
            ->toArray();

        return $errors;
    }

    /**
     * Update all provider health checks.
     */
    public function updateAllHealthChecks()
    {
        $providers = WebhookMetric::distinct('provider')->pluck('provider');

        foreach ($providers as $provider) {
            $healthCheck = WebhookHealthCheck::forProvider($provider);
            $healthCheck->updateFromMetrics();
        }
    }

    /**
     * Get health check for a provider.
     */
    public function getProviderHealth(string $provider): array
    {
        $healthCheck = WebhookHealthCheck::forProvider($provider);

        return [
            'provider' => $provider,
            'is_healthy' => $healthCheck->is_healthy,
            'health_status' => $healthCheck->getHealthStatus(),
            'status_color' => $healthCheck->getStatusColor(),
            'success_rate' => $healthCheck->success_rate,
            'uptime_percentage' => $healthCheck->uptime_percentage,
            'avg_response_time_ms' => $healthCheck->avg_response_time_ms,
            'p95_response_time_ms' => $healthCheck->p95_response_time_ms,
            'p99_response_time_ms' => $healthCheck->p99_response_time_ms,
            'circuit_state' => $healthCheck->circuit_state,
            'failures_count' => $healthCheck->failures_count,
            'successes_count' => $healthCheck->successes_count,
            'events_last_hour' => $healthCheck->events_last_hour,
            'events_last_24h' => $healthCheck->events_last_24h,
            'errors_last_hour' => $healthCheck->errors_last_hour,
            'errors_last_24h' => $healthCheck->errors_last_24h,
            'last_success_at' => $healthCheck->last_success_at?->toIso8601String(),
            'last_failure_at' => $healthCheck->last_failure_at?->toIso8601String(),
            'last_error_message' => $healthCheck->last_error_message,
        ];
    }

    /**
     * Get all providers' health status.
     */
    public function getAllProvidersHealth(): array
    {
        return WebhookHealthCheck::all()
            ->map(fn ($health) => $this->getProviderHealth($health->provider))
            ->toArray();
    }

    /**
     * Generate a health report.
     */
    public function generateHealthReport($timeRangeMinutes = 1440): array
    {
        $timeRange = [now()->subMinutes($timeRangeMinutes), now()];

        return [
            'report_generated_at' => now()->toIso8601String(),
            'time_range_minutes' => $timeRangeMinutes,
            'time_range_start' => $timeRange[0]->toIso8601String(),
            'time_range_end' => $timeRange[1]->toIso8601String(),
            'system_summary' => $this->getSystemSummary($timeRange),
            'providers' => $this->getProviderMetrics($timeRange),
            'overall_health' => WebhookHealthCheck::getOverallHealth(),
            'error_distribution' => $this->getErrorDistribution($timeRange),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Generate monitoring recommendations based on current state.
     */
    public function generateRecommendations(): array
    {
        $recommendations = [];

        // Check for open circuits
        $openCircuits = WebhookHealthCheck::circuitOpen()->count();
        if ($openCircuits > 0) {
            $recommendations[] = [
                'severity' => 'CRITICAL',
                'message' => "{$openCircuits} provider(s) have open circuit breakers",
                'action' => 'Investigate and resolve provider issues',
            ];
        }

        // Check for high latency
        $highLatency = WebhookHealthCheck::highLatency()->count();
        if ($highLatency > 0) {
            $recommendations[] = [
                'severity' => 'WARNING',
                'message' => "{$highLatency} provider(s) experiencing high latency",
                'action' => 'Monitor latency trends and scale if necessary',
            ];
        }

        // Check for degraded providers
        $degraded = WebhookHealthCheck::degraded()->count();
        if ($degraded > 0) {
            $recommendations[] = [
                'severity' => 'WARNING',
                'message' => "{$degraded} provider(s) with success rate < 99%",
                'action' => 'Review error logs and investigate failures',
            ];
        }

        // Check for no issues
        if (empty($recommendations)) {
            $recommendations[] = [
                'severity' => 'INFO',
                'message' => 'All webhook providers operating normally',
                'action' => 'Continue monitoring',
            ];
        }

        return $recommendations;
    }

    /**
     * Helper: Calculate percentile from metrics collection.
     */
    private function getPercentileResponseTime($metrics, int $percentile): float
    {
        if ($metrics->isEmpty()) {
            return 0;
        }

        $times = $metrics->pluck('response_time_ms')
            ->sort()
            ->values();

        $position = ceil(($percentile / 100) * $times->count()) - 1;
        return (float) ($times[$position] ?? 0);
    }

    /**
     * Export metrics for external monitoring (Datadog, New Relic, etc).
     */
    public function exportMetricsFormat(string $format = 'prometheus'): string
    {
        $metrics = WebhookMetric::recent(60)->get();

        if ($format === 'prometheus') {
            return $this->formatPrometheus($metrics);
        }

        if ($format === 'json') {
            return json_encode($this->getSystemSummary(), JSON_PRETTY_PRINT);
        }

        throw new \InvalidArgumentException("Unknown export format: {$format}");
    }

    /**
     * Format metrics as Prometheus exposition format.
     */
    private function formatPrometheus($metrics): string
    {
        $output = "# HELP webhook_metrics Webhook processing metrics\n";
        $output .= "# TYPE webhook_metrics gauge\n";

        $summary = $this->getSystemSummary();

        $output .= "webhook_total_events {$summary['total_events']}\n";
        $output .= "webhook_successful_events {$summary['successful_events']}\n";
        $output .= "webhook_failed_events {$summary['failed_events']}\n";
        $output .= "webhook_success_rate {$summary['success_rate']}\n";
        $output .= "webhook_avg_response_time_ms {$summary['avg_response_time_ms']}\n";
        $output .= "webhook_p95_response_time_ms {$summary['p95_response_time_ms']}\n";
        $output .= "webhook_p99_response_time_ms {$summary['p99_response_time_ms']}\n";

        return $output;
    }
}

<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\WebhookHealthCheck;
use App\Models\WebhookMetric;
use App\Services\Webhooks\WebhookObservabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Webhook Monitoring API Controller
 * 
 * Provides monitoring endpoints for external systems:
 * - Prometheus metrics export
 * - Health status checks
 * - SLA metrics
 * - Real-time alerts
 */
class WebhookMonitoringController extends Controller
{
    public function __construct(
        private WebhookObservabilityService $observability
    ) {}

    /**
     * Export metrics in Prometheus format.
     * Endpoint: GET /api/webhooks/metrics/prometheus
     */
    public function prometheusMetrics(): Response
    {
        $metrics = $this->observability->exportMetricsFormat('prometheus');

        return response($metrics, 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    /**
     * Get real-time dashboard data.
     * Endpoint: GET /api/webhooks/dashboard
     */
    public function dashboard(Request $request): array
    {
        $timeRangeMinutes = (int) $request->get('range', 60);

        return $this->observability->getDashboardData($timeRangeMinutes);
    }

    /**
     * Get system health status.
     * Endpoint: GET /api/webhooks/health
     */
    public function systemHealth(): array
    {
        $health = WebhookHealthCheck::getOverallHealth();
        $summary = $this->observability->getSystemSummary();

        return [
            'status' => match ($health['overall_status']) {
                'HEALTHY' => 'ok',
                'WARNING' => 'partial',
                'CRITICAL' => 'error',
            },
            'code' => match ($health['overall_status']) {
                'HEALTHY' => 200,
                'WARNING' => 207,
                'CRITICAL' => 503,
            },
            'timestamp' => now()->toIso8601String(),
            'health' => $health,
            'summary' => $summary,
        ];
    }

    /**
     * Get health status for a specific provider.
     * Endpoint: GET /api/webhooks/health/{provider}
     */
    public function providerHealth(string $provider): array
    {
        return $this->observability->getProviderHealth($provider);
    }

    /**
     * Get KPI metrics.
     * Endpoint: GET /api/webhooks/kpis
     */
    public function kpis(Request $request): array
    {
        $timeRange = [
            now()->subHours($request->get('hours', 1)),
            now(),
        ];

        $summary = $this->observability->getSystemSummary($timeRange);

        return [
            'success_rate' => [
                'value' => round($summary['success_rate'], 2),
                'threshold' => 99.0,
                'status' => $summary['success_rate'] >= 99.0 ? 'ok' : 'alert',
            ],
            'error_rate' => [
                'value' => round($summary['error_rate'], 2),
                'threshold' => 1.0,
                'status' => $summary['error_rate'] <= 1.0 ? 'ok' : 'alert',
            ],
            'avg_latency_ms' => [
                'value' => round($summary['avg_response_time_ms'], 2),
                'threshold' => 1000,
                'status' => $summary['avg_response_time_ms'] <= 1000 ? 'ok' : 'alert',
            ],
            'p95_latency_ms' => [
                'value' => round($summary['p95_response_time_ms'], 2),
                'threshold' => 2000,
                'status' => $summary['p95_response_time_ms'] <= 2000 ? 'ok' : 'alert',
            ],
            'p99_latency_ms' => [
                'value' => round($summary['p99_response_time_ms'], 2),
                'threshold' => 5000,
                'status' => $summary['p99_response_time_ms'] <= 5000 ? 'ok' : 'alert',
            ],
            'throughput_per_minute' => [
                'value' => round($summary['throughput_per_minute'], 2),
                'threshold' => 0,  // No minimum threshold
                'status' => 'ok',
            ],
        ];
    }

    /**
     * Get SLA tracking metrics.
     * Endpoint: GET /api/webhooks/sla
     */
    public function slaMetrics(Request $request): array
    {
        $timeRange = [
            now()->subDays($request->get('days', 7)),
            now(),
        ];

        $providers = WebhookHealthCheck::all();

        $sla = $providers->map(function ($provider) use ($timeRange) {
            $metrics = WebhookMetric::byProvider($provider->provider)
                ->inTimeRange($timeRange[0], $timeRange[1])
                ->get();

            if ($metrics->isEmpty()) {
                $uptime = 100;
                $successRate = 100;
            } else {
                $successful = $metrics->where('success', true)->count();
                $successRate = ($successful / $metrics->count()) * 100;
                $uptime = $successRate >= 99 ? 100 : round($successRate, 2);
            }

            return [
                'provider' => $provider->provider,
                'uptime_percentage' => $uptime,
                'sla_target' => 99.9,
                'compliant' => $uptime >= 99.9,
                'total_events' => $metrics->count(),
                'successful_events' => $metrics->where('success', true)->count(),
                'failed_events' => $metrics->where('success', false)->count(),
            ];
        })->toArray();

        return [
            'time_range_days' => $request->get('days', 7),
            'time_range_start' => $timeRange[0]->toIso8601String(),
            'time_range_end' => $timeRange[1]->toIso8601String(),
            'overall_sla_compliance' => array_every($sla, fn ($s) => $s['compliant']),
            'providers' => $sla,
        ];
    }

    /**
     * Get alerting thresholds and current status.
     * Endpoint: GET /api/webhooks/alerts
     */
    public function alertStatus(): array
    {
        $health = WebhookHealthCheck::getOverallHealth();
        $recommendations = $this->observability->generateRecommendations();

        $alerts = [];

        // Check for open circuits
        if ($health['circuit_open_providers'] > 0) {
            $alerts[] = [
                'level' => 'CRITICAL',
                'type' => 'CIRCUIT_BREAKER_OPEN',
                'count' => $health['circuit_open_providers'],
                'message' => "{$health['circuit_open_providers']} provider(s) have open circuit breakers",
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Check for degraded providers
        if ($health['degraded_providers'] > 0) {
            $alerts[] = [
                'level' => 'WARNING',
                'type' => 'PROVIDER_DEGRADED',
                'count' => $health['degraded_providers'],
                'message' => "{$health['degraded_providers']} provider(s) experiencing issues",
                'timestamp' => now()->toIso8601String(),
            ];
        }

        // Check SLA compliance
        $summary = $this->observability->getSystemSummary([
            now()->subHours(1),
            now(),
        ]);

        if ($summary['success_rate'] < 99) {
            $alerts[] = [
                'level' => 'WARNING',
                'type' => 'LOW_SUCCESS_RATE',
                'value' => round($summary['success_rate'], 2),
                'threshold' => 99,
                'message' => "Success rate below threshold: {$summary['success_rate']}%",
                'timestamp' => now()->toIso8601String(),
            ];
        }

        if ($summary['avg_response_time_ms'] > 1000) {
            $alerts[] = [
                'level' => 'WARNING',
                'type' => 'HIGH_LATENCY',
                'value' => round($summary['avg_response_time_ms'], 2),
                'threshold' => 1000,
                'message' => "Average latency above threshold: {$summary['avg_response_time_ms']}ms",
                'timestamp' => now()->toIso8601String(),
            ];
        }

        return [
            'total_alerts' => count($alerts),
            'critical_alerts' => count(array_filter($alerts, fn ($a) => $a['level'] === 'CRITICAL')),
            'warning_alerts' => count(array_filter($alerts, fn ($a) => $a['level'] === 'WARNING')),
            'alerts' => $alerts,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Get detailed error analysis.
     * Endpoint: GET /api/webhooks/errors
     */
    public function errorAnalysis(Request $request): array
    {
        $timeRange = [
            now()->subHours($request->get('hours', 24)),
            now(),
        ];

        $errorDistribution = $this->observability->getErrorDistribution($timeRange);
        
        $errors = WebhookMetric::failed()
            ->inTimeRange($timeRange[0], $timeRange[1])
            ->with('payment')  // If this relationship exists
            ->latest()
            ->limit(50)
            ->get();

        return [
            'time_range_hours' => $request->get('hours', 24),
            'time_range_start' => $timeRange[0]->toIso8601String(),
            'time_range_end' => $timeRange[1]->toIso8601String(),
            'total_errors' => count($errorDistribution),
            'error_distribution' => $errorDistribution,
            'recent_errors' => $errors->map(fn ($e) => [
                'id' => $e->id,
                'provider' => $e->provider,
                'event_type' => $e->event_type,
                'error_message' => $e->error_message,
                'response_time_ms' => $e->response_time_ms,
                'created_at' => $e->created_at->toIso8601String(),
            ])->toArray(),
        ];
    }

    /**
     * Get performance trends.
     * Endpoint: GET /api/webhooks/trends
     */
    public function performanceTrends(Request $request): array
    {
        $hours = (int) $request->get('hours', 24);
        $timeRange = [
            now()->subHours($hours),
            now(),
        ];

        $buckets = min((int) $request->get('buckets', 24), 100);
        $trends = $this->observability->getPerformanceTrends($timeRange, $buckets);

        return [
            'time_range_hours' => $hours,
            'bucket_count' => $buckets,
            'trends' => $trends,
        ];
    }

    /**
     * Generate comprehensive health report.
     * Endpoint: GET /api/webhooks/report
     */
    public function generateReport(Request $request): array
    {
        $days = (int) $request->get('days', 1);
        $report = $this->observability->generateHealthReport($days * 1440);

        return $report;
    }

    /**
     * Get export metrics in JSON format.
     * Endpoint: GET /api/webhooks/metrics/json
     */
    public function metricsJson(): array
    {
        return json_decode(
            $this->observability->exportMetricsFormat('json'),
            true
        );
    }

    /**
     * Webhook monitoring status page.
     * Simple HTML status for integration with monitoring dashboards.
     * Endpoint: GET /webhooks/status
     */
    public function statusPage(): Response
    {
        $health = WebhookHealthCheck::getOverallHealth();
        $summary = $this->observability->getSystemSummary();

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Monitoring Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .status { padding: 20px; border-radius: 5px; margin: 10px 0; }
        .healthy { background: #d4edda; color: #155724; }
        .warning { background: #fff3cd; color: #856404; }
        .critical { background: #f8d7da; color: #721c24; }
        .metric { padding: 10px; background: white; margin: 5px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Webhook Monitoring</h1>
    <div class="status {$this->getStatusClass($health['overall_status'])}">
        <h2>Overall Status: {$health['overall_status']}</h2>
        <p>Health: {$health['health_percentage']}% | Providers: {$health['total_providers']}</p>
    </div>
    <div class="metric">Success Rate: {$summary['success_rate']}%</div>
    <div class="metric">Avg Response Time: {$summary['avg_response_time_ms']}ms</div>
    <div class="metric">P95 Latency: {$summary['p95_response_time_ms']}ms</div>
    <div class="metric">Throughput: {$summary['throughput_per_minute']} events/min</div>
</body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Helper to get status CSS class.
     */
    private function getStatusClass(string $status): string
    {
        return match ($status) {
            'HEALTHY' => 'healthy',
            'WARNING' => 'warning',
            'CRITICAL' => 'critical',
            default => 'warning',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Webhook Health Check Model
 * 
 * Tracks provider health status, SLAs, and circuit breaker state.
 * One record per webhook provider.
 */
class WebhookHealthCheck extends Model
{
    protected $table = 'webhook_health_checks';

    protected $fillable = [
        'provider',
        'endpoint_url',
        'is_healthy',
        'success_rate',
        'uptime_percentage',
        'avg_response_time_ms',
        'p95_response_time_ms',
        'p99_response_time_ms',
        'circuit_state',
        'failures_count',
        'successes_count',
        'events_last_hour',
        'events_last_24h',
        'errors_last_hour',
        'errors_last_24h',
        'last_checked_at',
        'last_success_at',
        'last_failure_at',
        'last_error_message',
        'alert_on_high_latency',
        'alert_on_circuit_open',
        'latency_threshold_ms',
    ];

    protected $casts = [
        'is_healthy' => 'boolean',
        'alert_on_high_latency' => 'boolean',
        'alert_on_circuit_open' => 'boolean',
        'last_checked_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_failure_at' => 'datetime',
    ];

    // ========================================================================
    // ACCESSORS
    // ========================================================================

    /**
     * Check if provider is currently degraded.
     */
    public function isDegraded(): bool
    {
        return $this->success_rate < 99 || $this->is_healthy === false;
    }

    /**
     * Check if circuit breaker is open.
     */
    public function isCircuitOpen(): bool
    {
        return $this->circuit_state === 'open';
    }

    /**
     * Get health status as human-readable string.
     */
    public function getHealthStatus(): string
    {
        if ($this->isCircuitOpen()) {
            return 'CRITICAL - Circuit Open';
        }

        if (!$this->is_healthy) {
            return 'UNHEALTHY';
        }

        if ($this->isDegraded()) {
            return 'DEGRADED';
        }

        return 'HEALTHY';
    }

    /**
     * Get color indicator for status (for UI).
     */
    public function getStatusColor(): string
    {
        return match ($this->getHealthStatus()) {
            'CRITICAL - Circuit Open' => 'danger',
            'UNHEALTHY' => 'danger',
            'DEGRADED' => 'warning',
            'HEALTHY' => 'success',
            default => 'secondary',
        };
    }

    // ========================================================================
    // METHODS FOR UPDATING HEALTH STATUS
    // ========================================================================

    /**
     * Update health status based on recent metrics.
     */
    public function updateFromMetrics()
    {
        $recentMetrics = WebhookMetric::byProvider($this->provider)
            ->recent(60)  // Last hour
            ->get();

        if ($recentMetrics->isEmpty()) {
            return;  // No recent metrics
        }

        // Calculate metrics
        $totalCount = $recentMetrics->count();
        $successCount = $recentMetrics->where('success', true)->count();
        $failureCount = $recentMetrics->where('success', false)->count();
        $avgResponseTime = $recentMetrics->avg('response_time_ms');

        // Update health check record
        $this->update([
            'events_last_hour' => $totalCount,
            'errors_last_hour' => $failureCount,
            'success_rate' => $totalCount > 0 ? ($successCount / $totalCount) * 100 : 100,
            'avg_response_time_ms' => (int) $avgResponseTime,
            'p95_response_time_ms' => (int) WebhookMetric::percentileResponseTime($this->provider, 95),
            'p99_response_time_ms' => (int) WebhookMetric::percentileResponseTime($this->provider, 99),
            'last_checked_at' => now(),
            'is_healthy' => $failureCount === 0 && $avgResponseTime < $this->latency_threshold_ms,
        ]);
    }

    /**
     * Record successful delivery.
     */
    public function recordSuccess()
    {
        $this->increment('successes_count');
        $this->update([
            'last_success_at' => now(),
        ]);
    }

    /**
     * Record failed delivery.
     */
    public function recordFailure(string $errorMessage = null)
    {
        $this->increment('failures_count');
        $this->update([
            'last_failure_at' => now(),
            'last_error_message' => $errorMessage,
            'is_healthy' => false,
        ]);
    }

    /**
     * Update circuit breaker state.
     */
    public function updateCircuitState(string $state)
    {
        $this->update([
            'circuit_state' => $state,
        ]);
    }

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Get only healthy providers.
     */
    public function scopeHealthy($query)
    {
        return $query->where('is_healthy', true)
            ->where('circuit_state', 'closed');
    }

    /**
     * Get degraded providers.
     */
    public function scopeDegraded($query)
    {
        return $query->where(function ($q) {
            $q->where('success_rate', '<', 99)
                ->orWhere('is_healthy', false);
        });
    }

    /**
     * Get providers with open circuits.
     */
    public function scopeCircuitOpen($query)
    {
        return $query->where('circuit_state', 'open');
    }

    /**
     * Get high latency providers.
     */
    public function scopeHighLatency($query)
    {
        return $query->whereColumn('avg_response_time_ms', '>', 'latency_threshold_ms');
    }

    // ========================================================================
    // FACTORY/HELPER METHODS
    // ========================================================================

    /**
     * Find or create health check for a provider.
     */
    public static function forProvider(string $provider)
    {
        return static::firstOrCreate(
            ['provider' => $provider],
            [
                'endpoint_url' => config("webhooks.providers.{$provider}.endpoint"),
                'is_healthy' => true,
                'circuit_state' => 'closed',
            ]
        );
    }

    /**
     * Get overall system health.
     */
    public static function getOverallHealth()
    {
        $total = static::count();
        $healthy = static::healthy()->count();
        $degraded = static::degraded()->count();
        $circuitOpen = static::circuitOpen()->count();

        return [
            'total_providers' => $total,
            'healthy_providers' => $healthy,
            'degraded_providers' => $degraded,
            'circuit_open_providers' => $circuitOpen,
            'overall_status' => $circuitOpen > 0 ? 'CRITICAL' : ($degraded > 0 ? 'WARNING' : 'HEALTHY'),
            'health_percentage' => $total > 0 ? ($healthy / $total) * 100 : 100,
        ];
    }
}

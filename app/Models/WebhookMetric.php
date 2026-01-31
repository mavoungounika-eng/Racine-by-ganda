<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Webhook Metrics Model
 * 
 * Tracks real-time performance metrics for webhook processing.
 * Used for observability, monitoring, and performance analysis.
 */
class WebhookMetric extends Model
{
    protected $table = 'webhook_metrics';

    protected $fillable = [
        'provider',
        'event_type',
        'webhook_id',
        'response_time_ms',
        'retry_count',
        'status_code',
        'handler',
        'success',
        'error_message',
        'received_at',
        'started_at',
        'completed_at',
        'circuit_state',
        'tags',
        'context',
    ];

    protected $casts = [
        'success' => 'boolean',
        'tags' => 'array',
        'context' => 'array',
        'received_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // ========================================================================
    // SCOPES
    // ========================================================================

    /**
     * Filter by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Filter by event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Filter successful metrics.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Filter failed metrics.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Filter by time range.
     */
    public function scopeInTimeRange($query, $startTime, $endTime)
    {
        return $query->whereBetween('created_at', [$startTime, $endTime]);
    }

    /**
     * Filter by response time threshold.
     */
    public function scopeSlowerthThan($query, int $ms)
    {
        return $query->where('response_time_ms', '>', $ms);
    }

    /**
     * Filter recent metrics (last N minutes).
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // ========================================================================
    // AGGREGATION METHODS
    // ========================================================================

    /**
     * Get average response time for a provider.
     */
    public static function averageResponseTimeByProvider(string $provider, $timeRange = null)
    {
        $query = static::byProvider($provider);

        if ($timeRange) {
            $query->inTimeRange($timeRange[0], $timeRange[1]);
        }

        return $query->avg('response_time_ms');
    }

    /**
     * Get success rate for a provider.
     */
    public static function successRateByProvider(string $provider, $timeRange = null)
    {
        $query = static::byProvider($provider);

        if ($timeRange) {
            $query->inTimeRange($timeRange[0], $timeRange[1]);
        }

        $total = (clone $query)->count();
        $successful = (clone $query)->successful()->count();

        return $total > 0 ? ($successful / $total) * 100 : 100;
    }

    /**
     * Get error rate for a provider.
     */
    public static function errorRateByProvider(string $provider, $timeRange = null)
    {
        return 100 - static::successRateByProvider($provider, $timeRange);
    }

    /**
     * Get throughput (events per minute).
     */
    public static function throughputByProvider(string $provider, int $minutes = 1)
    {
        $count = static::byProvider($provider)
            ->recent($minutes)
            ->count();

        return round($count / $minutes, 2);
    }

    /**
     * Get percentile response time.
     */
    public static function percentileResponseTime(string $provider, int $percentile, $timeRange = null)
    {
        $query = static::byProvider($provider);

        if ($timeRange) {
            $query->inTimeRange($timeRange[0], $timeRange[1]);
        }

        $times = $query->pluck('response_time_ms')->sort()->values();

        if ($times->isEmpty()) {
            return 0;
        }

        $position = ceil(($percentile / 100) * $times->count()) - 1;
        return $times[$position] ?? 0;
    }

    /**
     * Get comprehensive metrics summary.
     */
    public static function getSummary(string $provider, $timeRange = null)
    {
        return [
            'provider' => $provider,
            'total_events' => static::byProvider($provider)
                ->tap(fn ($q) => $timeRange ? $q->inTimeRange($timeRange[0], $timeRange[1]) : $q)
                ->count(),
            'successful' => static::byProvider($provider)->successful()
                ->tap(fn ($q) => $timeRange ? $q->inTimeRange($timeRange[0], $timeRange[1]) : $q)
                ->count(),
            'failed' => static::byProvider($provider)->failed()
                ->tap(fn ($q) => $timeRange ? $q->inTimeRange($timeRange[0], $timeRange[1]) : $q)
                ->count(),
            'success_rate' => static::successRateByProvider($provider, $timeRange),
            'error_rate' => static::errorRateByProvider($provider, $timeRange),
            'avg_response_time_ms' => static::averageResponseTimeByProvider($provider, $timeRange),
            'p95_response_time_ms' => static::percentileResponseTime($provider, 95, $timeRange),
            'p99_response_time_ms' => static::percentileResponseTime($provider, 99, $timeRange),
            'throughput_per_minute' => static::throughputByProvider($provider, 1),
        ];
    }

    /**
     * Get all providers' metrics summary.
     */
    public static function getAllProvidersSummary($timeRange = null)
    {
        $providers = static::distinct('provider')->pluck('provider');

        return $providers->map(fn ($provider) => static::getSummary($provider, $timeRange))->toArray();
    }
}

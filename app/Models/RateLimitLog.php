<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimitLog extends Model
{
    protected $table = 'rate_limit_logs';

    protected $fillable = [
        'key',
        'endpoint',
        'ip_address',
        'user_id',
        'limit',
        'window_seconds',
        'requests_in_window',
        'was_blocked',
    ];

    protected $casts = [
        'was_blocked' => 'boolean',
        'requests_in_window' => 'integer',
        'limit' => 'integer',
    ];

    /**
     * Get violations (blocked requests) for analytics
     */
    public static function getViolations($hours = 24)
    {
        return self::where('was_blocked', true)
            ->where('created_at', '>=', now()->subHours($hours))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get repeated offenders (same IP/user multiple violations)
     */
    public static function getRepeatedOffenders($minViolations = 5, $hours = 24)
    {
        return self::where('was_blocked', true)
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('key, COUNT(*) as violation_count')
            ->groupBy('key')
            ->having('violation_count', '>=', $minViolations)
            ->orderByDesc('violation_count')
            ->get();
    }

    /**
     * Get endpoint statistics
     */
    public static function getEndpointStats($hours = 24)
    {
        return self::where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('endpoint, COUNT(*) as total_requests, SUM(CASE WHEN was_blocked THEN 1 ELSE 0 END) as blocked_count')
            ->groupBy('endpoint')
            ->orderByDesc('total_requests')
            ->get();
    }
}

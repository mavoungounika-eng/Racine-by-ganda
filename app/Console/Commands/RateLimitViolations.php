<?php

namespace App\Console\Commands;

use App\Models\RateLimitLog;
use Illuminate\Console\Command;

class RateLimitViolations extends Command
{
    protected $signature = 'rate-limit:violations {--hours=24 : Hours to look back} {--offenders : Show repeated offenders} {--endpoint= : Filter by endpoint}';

    protected $description = 'View rate limit violations and statistics';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $showOffenders = $this->option('offenders');
        $endpoint = $this->option('endpoint');

        $this->info("Rate Limit Violations - Last {$hours} hours\n");

        // 1. Overall statistics
        $this->showStatistics($hours);

        // 2. Endpoint statistics
        $this->showEndpointStats($hours, $endpoint);

        // 3. Repeated offenders (if requested)
        if ($showOffenders) {
            $this->showRepeatedOffenders($hours);
        }

        return 0;
    }

    private function showStatistics($hours): void
    {
        $violations = RateLimitLog::where('was_blocked', true)
            ->where('created_at', '>=', now()->subHours($hours))
            ->count();

        $total = RateLimitLog::where('created_at', '>=', now()->subHours($hours))
            ->count();

        $rate = $total > 0 ? round(($violations / $total) * 100, 2) : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', $total],
                ['Blocked Requests (429)', $violations],
                ['Block Rate', "{$rate}%"],
            ]
        );

        $this->newLine();
    }

    private function showEndpointStats($hours, $endpoint = null): void
    {
        $stats = RateLimitLog::where('created_at', '>=', now()->subHours($hours));

        if ($endpoint) {
            $stats = $stats->where('endpoint', $endpoint);
        }

        $stats = $stats
            ->selectRaw('endpoint, COUNT(*) as total_requests, SUM(CASE WHEN was_blocked THEN 1 ELSE 0 END) as blocked_count')
            ->groupBy('endpoint')
            ->orderByDesc('total_requests')
            ->get();

        if ($stats->isEmpty()) {
            $this->warn('No violations found.');
            return;
        }

        $rows = $stats->map(function ($stat) {
            $blockRate = $stat->total_requests > 0 
                ? round(($stat->blocked_count / $stat->total_requests) * 100, 1)
                : 0;
            
            return [
                $stat->endpoint,
                $stat->total_requests,
                $stat->blocked_count,
                "{$blockRate}%",
            ];
        })->toArray();

        $this->table(
            ['Endpoint', 'Total Requests', 'Blocked', 'Block Rate'],
            $rows
        );

        $this->newLine();
    }

    private function showRepeatedOffenders($hours): void
    {
        $this->info('Repeated Offenders (≥5 violations in last ' . $hours . ' hours)');
        $this->line(str_repeat('-', 80));

        $offenders = RateLimitLog::where('was_blocked', true)
            ->where('created_at', '>=', now()->subHours($hours))
            ->selectRaw('key, COUNT(*) as violation_count, MAX(created_at) as last_violation')
            ->groupBy('key')
            ->having('violation_count', '>=', 5)
            ->orderByDesc('violation_count')
            ->get();

        if ($offenders->isEmpty()) {
            $this->info('No repeated offenders found.');
            return;
        }

        $rows = $offenders->map(function ($offender) {
            return [
                $offender->key,
                $offender->violation_count,
                $offender->last_violation->diffForHumans(),
            ];
        })->toArray();

        $this->table(
            ['Key (IP/User)', 'Violations', 'Last Violation'],
            $rows
        );

        $this->newLine();

        $this->warn('Consider implementing IP whitelist or increasing limits for legitimate users.');
    }
}

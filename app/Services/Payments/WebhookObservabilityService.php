<?php

namespace App\Services\Payments;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebhookObservabilityService
{
    /**
     * Obtenir un résumé des métriques webhooks
     *
     * @param array $options
     * @return array
     */
    public function getSummary(array $options = []): array
    {
        $windowMinutes = $options['window_minutes'] ?? 60;
        $thresholdMinutes = $options['threshold_minutes'] ?? 10;
        $cacheKey = "webhook_observability_summary_{$windowMinutes}_{$thresholdMinutes}";

        return Cache::remember($cacheKey, 60, function () use ($windowMinutes, $thresholdMinutes) {
            return $this->calculateSummary($windowMinutes, $thresholdMinutes);
        });
    }

    /**
     * Obtenir un résumé étendu (24h) pour monitoring
     *
     * @param array $options
     * @return array
     */
    public function getExtendedSummary(array $options = []): array
    {
        $window24h = $options['window_24h_minutes'] ?? 1440; // 24h
        $window1h = $options['window_1h_minutes'] ?? 60;
        $thresholdMinutes = $options['threshold_minutes'] ?? 10;
        $cacheKey = "webhook_observability_extended_{$window24h}_{$window1h}_{$thresholdMinutes}";

        return Cache::remember($cacheKey, 300, function () use ($window24h, $window1h, $thresholdMinutes) {
            $summary24h = $this->calculateSummary($window24h, $thresholdMinutes);
            $summary1h = $this->calculateSummary($window1h, $thresholdMinutes);

            return [
                'last_24h' => $summary24h,
                'last_1h' => $summary1h,
            ];
        });
    }

    /**
     * Calculer le résumé (sans cache)
     *
     * @param int $windowMinutes
     * @param int $thresholdMinutes
     * @return array
     */
    private function calculateSummary(int $windowMinutes, int $thresholdMinutes): array
    {
        $windowStart = now()->subMinutes($windowMinutes);
        $thresholdTime = now()->subMinutes($thresholdMinutes);

        // Stripe events
        $stripeTotals = $this->getStripeTotals($windowStart);
        $stripeCountsByStatus = $this->getStripeCountsByStatus($windowStart);
        $stripeStuck = $this->getStripeStuckCounts($thresholdTime);
        $stripeLastEvent = $this->getStripeLastEventAt();
        $stripeBlocked = $this->getStripeBlockedCount();
        $stripeLatency = $this->getStripeAverageLatency($windowStart);

        // Monetbil events
        $monetbilTotals = $this->getMonetbilTotals($windowStart);
        $monetbilCountsByStatus = $this->getMonetbilCountsByStatus($windowStart);
        $monetbilStuck = $this->getMonetbilStuckCounts($thresholdTime);
        $monetbilLastEvent = $this->getMonetbilLastEventAt();
        $monetbilBlocked = $this->getMonetbilBlockedCount();
        $monetbilLatency = $this->getMonetbilAverageLatency($windowStart);

        return [
            'totals_by_provider' => [
                'stripe' => $stripeTotals,
                'monetbil' => $monetbilTotals,
            ],
            'counts_by_status' => [
                'stripe' => $stripeCountsByStatus,
                'monetbil' => $monetbilCountsByStatus,
            ],
            'stuck_counts' => [
                'stripe' => $stripeStuck,
                'monetbil' => $monetbilStuck,
                'total' => $stripeStuck['total'] + $monetbilStuck['total'],
            ],
            'last_event_at' => [
                'stripe' => $stripeLastEvent,
                'monetbil' => $monetbilLastEvent,
            ],
            'blocked_counts' => [
                'stripe' => $stripeBlocked,
                'monetbil' => $monetbilBlocked,
                'total' => $stripeBlocked + $monetbilBlocked,
            ],
            'average_latency_seconds' => [
                'stripe' => $stripeLatency,
                'monetbil' => $monetbilLatency,
            ],
            'window_minutes' => $windowMinutes,
            'threshold_minutes' => $thresholdMinutes,
        ];
    }

    /**
     * Totaux Stripe (window)
     *
     * @param \Carbon\Carbon $windowStart
     * @return int
     */
    private function getStripeTotals($windowStart): int
    {
        return StripeWebhookEvent::where('created_at', '>=', $windowStart)->count();
    }

    /**
     * Comptes par status Stripe (window)
     *
     * @param \Carbon\Carbon $windowStart
     * @return array
     */
    private function getStripeCountsByStatus($windowStart): array
    {
        $counts = StripeWebhookEvent::where('created_at', '>=', $windowStart)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // S'assurer que tous les status sont présents (même à 0)
        return array_merge([
            'received' => 0,
            'processed' => 0,
            'failed' => 0,
            'ignored' => 0,
            'blocked' => 0,
        ], $counts);
    }

    /**
     * Comptes stuck Stripe
     *
     * @param \Carbon\Carbon $thresholdTime
     * @return array
     */
    private function getStripeStuckCounts($thresholdTime): array
    {
        $nullDispatched = StripeWebhookEvent::whereIn('status', ['received', 'failed'])
            ->whereNull('dispatched_at')
            ->count();

        $failedOld = StripeWebhookEvent::where('status', 'failed')
            ->whereNotNull('dispatched_at')
            ->where('dispatched_at', '<', $thresholdTime)
            ->count();

        return [
            'null_dispatched_at' => $nullDispatched,
            'failed_old' => $failedOld,
            'total' => $nullDispatched + $failedOld,
        ];
    }

    /**
     * Dernier event Stripe
     *
     * @return string|null
     */
    private function getStripeLastEventAt(): ?string
    {
        $event = StripeWebhookEvent::orderBy('created_at', 'desc')->first();
        return $event ? $event->created_at->toIso8601String() : null;
    }

    /**
     * Totaux Monetbil (window)
     *
     * @param \Carbon\Carbon $windowStart
     * @return int
     */
    private function getMonetbilTotals($windowStart): int
    {
        return MonetbilCallbackEvent::where('created_at', '>=', $windowStart)->count();
    }

    /**
     * Comptes par status Monetbil (window)
     *
     * @param \Carbon\Carbon $windowStart
     * @return array
     */
    private function getMonetbilCountsByStatus($windowStart): array
    {
        $counts = MonetbilCallbackEvent::where('created_at', '>=', $windowStart)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // S'assurer que tous les status sont présents (même à 0)
        return array_merge([
            'received' => 0,
            'processed' => 0,
            'failed' => 0,
            'ignored' => 0,
            'blocked' => 0,
        ], $counts);
    }

    /**
     * Comptes stuck Monetbil
     *
     * @param \Carbon\Carbon $thresholdTime
     * @return array
     */
    private function getMonetbilStuckCounts($thresholdTime): array
    {
        $nullDispatched = MonetbilCallbackEvent::whereIn('status', ['received', 'failed'])
            ->whereNull('dispatched_at')
            ->count();

        $failedOld = MonetbilCallbackEvent::where('status', 'failed')
            ->whereNotNull('dispatched_at')
            ->where('dispatched_at', '<', $thresholdTime)
            ->count();

        return [
            'null_dispatched_at' => $nullDispatched,
            'failed_old' => $failedOld,
            'total' => $nullDispatched + $failedOld,
        ];
    }

    /**
     * Dernier event Monetbil
     *
     * @return string|null
     */
    private function getMonetbilLastEventAt(): ?string
    {
        $event = MonetbilCallbackEvent::orderBy('created_at', 'desc')->first();
        return $event ? $event->created_at->toIso8601String() : null;
    }

    /**
     * Compte des events Stripe bloqués
     *
     * @return int
     */
    private function getStripeBlockedCount(): int
    {
        return StripeWebhookEvent::where('status', 'blocked')->count();
    }

    /**
     * Compte des events Monetbil bloqués
     *
     * @return int
     */
    private function getMonetbilBlockedCount(): int
    {
        return MonetbilCallbackEvent::where('status', 'blocked')->count();
    }

    /**
     * Latence moyenne Stripe (processed_at - created_at) en secondes
     *
     * @param \Carbon\Carbon $windowStart
     * @return float|null
     */
    private function getStripeAverageLatency($windowStart): ?float
    {
        $result = StripeWebhookEvent::where('created_at', '>=', $windowStart)
            ->where('status', 'processed')
            ->whereNotNull('processed_at')
            ->selectRaw('AVG(UNIX_TIMESTAMP(processed_at) - UNIX_TIMESTAMP(created_at)) as avg_latency')
            ->first();

        return $result && $result->avg_latency ? (float) $result->avg_latency : null;
    }

    /**
     * Latence moyenne Monetbil (processed_at - received_at ou created_at) en secondes
     *
     * @param \Carbon\Carbon $windowStart
     * @return float|null
     */
    private function getMonetbilAverageLatency($windowStart): ?float
    {
        $driver = \DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite : utiliser julianday
            $result = MonetbilCallbackEvent::where('created_at', '>=', $windowStart)
                ->where('status', 'processed')
                ->whereNotNull('processed_at')
                ->selectRaw("AVG((julianday(processed_at) - julianday(COALESCE(received_at, created_at))) * 86400) as avg_latency")
                ->first();
        } else {
            // MySQL/Postgres : UNIX_TIMESTAMP
            $result = MonetbilCallbackEvent::where('created_at', '>=', $windowStart)
                ->where('status', 'processed')
                ->whereNotNull('processed_at')
                ->selectRaw('AVG(UNIX_TIMESTAMP(processed_at) - UNIX_TIMESTAMP(COALESCE(received_at, created_at))) as avg_latency')
                ->first();
        }

        return $result && $result->avg_latency ? (float) $result->avg_latency : null;
    }
}





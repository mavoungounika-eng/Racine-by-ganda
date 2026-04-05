<?php

namespace App\Services\Financial;

use App\Models\CreatorSubscription;
use App\Models\CreatorStripeAccount;

class StrategicMetricsService
{
    public function calculateChurnRate(): float
    {
        $total = CreatorSubscription::whereIn('status', ['active', 'canceled'])
            ->where('started_at', '>=', now()->subMonth())
            ->count();

        if ($total === 0) return 0.0;

        $canceled = CreatorSubscription::where('status', 'canceled')
            ->whereNotNull('canceled_at')
            ->where('canceled_at', '>=', now()->startOfMonth())
            ->count();

        return round(($canceled / $total) * 100);
    }

    public function calculateARPU(): float
    {
        $active = CreatorSubscription::where('status', 'active')->count();
        if ($active === 0) return 0.0;
        $mrr = (new FinancialDashboardService())->calculateMRR();
        return $mrr / $active;
    }

    public function calculateStripeHealthScore(): array
    {
        $total = CreatorStripeAccount::count();
        if ($total === 0) {
            return ['score' => 0, 'charges_enabled_rate' => 0, 'payouts_enabled_rate' => 0, 'onboarding_complete_rate' => 0];
        }
        $c = round((CreatorStripeAccount::where('charges_enabled', true)->count() / $total) * 100);
        $p = round((CreatorStripeAccount::where('payouts_enabled', true)->count() / $total) * 100);
        $o = round((CreatorStripeAccount::where('onboarding_status', 'complete')->count() / $total) * 100);
        return ['score' => round(($c + $p + $o) / 3), 'charges_enabled_rate' => $c, 'payouts_enabled_rate' => $p, 'onboarding_complete_rate' => $o];
    }
}

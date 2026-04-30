<?php

namespace App\Services\Financial;

use App\Models\CreatorSubscription;

class FinancialDashboardService
{
    public function calculateMRR(): float
    {
        return CreatorSubscription::where('status', 'active')
            ->join('creator_plans', 'creator_subscriptions.creator_plan_id', '=', 'creator_plans.id')
            ->where('creator_plans.billing_cycle', 'monthly')
            ->sum('creator_plans.price');
    }

    public function calculateARR(): float
    {
        return $this->calculateMRR() * 12;
    }

    public function getTotalActiveSubscriptions(): int
    {
        return CreatorSubscription::where('status', 'active')->count();
    }

    public function getDashboardMetrics(): array
    {
        $mrr = $this->calculateMRR();
        $active = $this->getTotalActiveSubscriptions();
        return [
            'revenue' => ['mrr' => $mrr, 'arr' => $mrr * 12],
            'subscriptions' => ['active' => $active],
            'creators' => ['active' => $active],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\SubscriptionEvent;
use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use Illuminate\Support\Facades\DB;

class SubscriptionAnalyticsService
{
    public function calculateMRR(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');

        $subscriptions = CreatorSubscription::where('status', 'active')
            ->whereHas('plan', function ($query) {
                $query->whereNotIn('code', ['free']);
            })
            ->whereYear('started_at', substr($month, 0, 4))
            ->whereMonth('started_at', substr($month, 5, 2))
            ->with('plan')
            ->get();

        return $subscriptions->sum(function ($subscription) {
            return $subscription->plan->price ?? 0;
        });
    }

    public function calculateConversionRate(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $startDate = "{$month}-01";
        $endDate = now()->parse($startDate)->endOfMonth()->format('Y-m-d');

        // Atelier = point d'entrée (remplace free)
        $atelierToMaison = SubscriptionEvent::where('event', 'upgraded')
            ->whereHas('fromPlan', function ($q) {
                $q->where('code', 'atelier');
            })
            ->whereHas('toPlan', function ($q) {
                $q->where('code', 'maison');
            })
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();

        $totalAtelier = CreatorSubscription::whereHas('plan', function ($q) {
            $q->where('code', 'atelier');
        })
        ->where('status', 'active')
        ->where('started_at', '<=', $endDate)
        ->count();

        $rate = $totalAtelier > 0 ? ($atelierToMaison / $totalAtelier) * 100 : 0;

        return [
            'atelier_to_maison' => $atelierToMaison,
            'total_atelier'     => $totalAtelier,
            'rate'              => round($rate, 2),
        ];
    }

    public function calculateChurn(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $startDate = "{$month}-01";
        $endDate = now()->parse($startDate)->endOfMonth()->format('Y-m-d');

        $canceled = SubscriptionEvent::where('event', 'canceled')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();

        $downgraded = SubscriptionEvent::where('event', 'downgraded')
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();

        $totalActive = CreatorSubscription::where('status', 'active')
            ->where('started_at', '<=', $startDate)
            ->count();

        $churnRate = $totalActive > 0 ? (($canceled + $downgraded) / $totalActive) * 100 : 0;

        return [
            'canceled'     => $canceled,
            'downgraded'   => $downgraded,
            'total_active' => $totalActive,
            'churn_rate'   => round($churnRate, 2),
        ];
    }

    public function getGlobalStats(): array
    {
        $totalCreators    = CreatorSubscription::distinct('creator_id')->count();
        $withSubscription = CreatorSubscription::where('status', 'active')->distinct('creator_id')->count();
        $trialPlan        = CreatorSubscription::whereHas('plan', function ($q) {
            $q->where('code', 'atelier');
        })->where('status', 'active')->count();
        $paidPlans = CreatorSubscription::whereHas('plan', function ($q) {
            $q->whereIn('code', ['maison', 'signature']);
        })->where('status', 'active')->count();

        return [
            'total_creators'   => $totalCreators,
            'with_subscription'=> $withSubscription,
            'atelier_plan'     => $trialPlan,
            'paid_plans'       => $paidPlans,
            'mrr'              => $this->calculateMRR(),
            'conversion'       => $this->calculateConversionRate(),
            'churn'            => $this->calculateChurn(),
        ];
    }

    public function trackEvent(
        int $creatorId,
        string $event,
        ?int $fromPlanId = null,
        ?int $toPlanId = null,
        ?float $amount = null,
        ?array $metadata = null
    ): SubscriptionEvent {
        return SubscriptionEvent::create([
            'creator_id'   => $creatorId,
            'event'        => $event,
            'from_plan_id' => $fromPlanId,
            'to_plan_id'   => $toPlanId,
            'amount'       => $amount,
            'occurred_at'  => now(),
            'metadata'     => $metadata,
        ]);
    }
}

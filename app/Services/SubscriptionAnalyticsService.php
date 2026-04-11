<?php

namespace App\Services;

use App\Models\SubscriptionEvent;
use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use Illuminate\Support\Facades\DB;

/**
 * SubscriptionAnalyticsService
 * 
 * Service pour calculer les métriques d'abonnement (MRR, churn, conversion, etc.)
 */
class SubscriptionAnalyticsService
{
    /**
     * Calculer le MRR (Monthly Recurring Revenue).
     * 
     * @param string|null $month Format: 'Y-m' (ex: '2025-12')
     * @return float
     */
    public function calculateMRR(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        
        $subscriptions = CreatorSubscription::where('status', 'active')
            ->whereHas('plan', function ($query) {
                $query->where('code', '!=', 'free');
            })
            ->whereYear('started_at', substr($month, 0, 4))
            ->whereMonth('started_at', substr($month, 5, 2))
            ->with('plan')
            ->get();

        return $subscriptions->sum(function ($subscription) {
            return $subscription->plan->price ?? 0;
        });
    }

    /**
     * Calculer le taux de conversion FREE → OFFICIEL.
     * 
     * @param string|null $month
     * @return array
     */
    public function calculateConversionRate(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');
        $startDate = "{$month}-01";
        $endDate = now()->parse($startDate)->endOfMonth()->format('Y-m-d');

        $freeToOfficial = SubscriptionEvent::where('event', 'upgraded')
            ->whereHas('fromPlan', function ($q) {
                $q->where('code', 'free');
            })
            ->whereHas('toPlan', function ($q) {
                $q->where('code', 'official');
            })
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->count();

        $totalFree = CreatorSubscription::whereHas('plan', function ($q) {
            $q->where('code', 'free');
        })
        ->where('status', 'active')
        ->where('started_at', '<=', $endDate)
        ->count();

        $rate = $totalFree > 0 ? ($freeToOfficial / $totalFree) * 100 : 0;

        return [
            'free_to_official' => $freeToOfficial,
            'total_free' => $totalFree,
            'rate' => round($rate, 2),
        ];
    }

    /**
     * Calculer le churn mensuel.
     * 
     * @param string|null $month
     * @return array
     */
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
            'canceled' => $canceled,
            'downgraded' => $downgraded,
            'total_active' => $totalActive,
            'churn_rate' => round($churnRate, 2),
        ];
    }

    /**
     * Obtenir les statistiques globales.
     * 
     * @return array
     */
    public function getGlobalStats(): array
    {
        $totalCreators = CreatorSubscription::distinct('creator_id')->count();
        $withSubscription = CreatorSubscription::where('status', 'active')->distinct('creator_id')->count();
        $freePlan = CreatorSubscription::whereHas('plan', function ($q) {
            $q->where('code', 'free');
        })->where('status', 'active')->count();
        $paidPlans = CreatorSubscription::whereHas('plan', function ($q) {
            $q->where('code', '!=', 'free');
        })->where('status', 'active')->count();

        return [
            'total_creators' => $totalCreators,
            'with_subscription' => $withSubscription,
            'free_plan' => $freePlan,
            'paid_plans' => $paidPlans,
            'mrr' => $this->calculateMRR(),
            'conversion' => $this->calculateConversionRate(),
            'churn' => $this->calculateChurn(),
        ];
    }

    /**
     * Enregistrer un événement d'abonnement.
     * 
     * @param int $creatorId
     * @param string $event
     * @param int|null $fromPlanId
     * @param int|null $toPlanId
     * @param float|null $amount
     * @param array|null $metadata
     * @return SubscriptionEvent
     */
    public function trackEvent(
        int $creatorId,
        string $event,
        ?int $fromPlanId = null,
        ?int $toPlanId = null,
        ?float $amount = null,
        ?array $metadata = null
    ): SubscriptionEvent {
        return SubscriptionEvent::create([
            'creator_id' => $creatorId,
            'event' => $event,
            'from_plan_id' => $fromPlanId,
            'to_plan_id' => $toPlanId,
            'amount' => $amount,
            'occurred_at' => now(),
            'metadata' => $metadata,
        ]);
    }
}


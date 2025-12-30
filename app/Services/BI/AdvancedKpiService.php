<?php

namespace App\Services\BI;

use App\Models\CreatorSubscription;
use App\Models\CreatorSubscriptionInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service BI - KPI Avancés (Analytique)
 * 
 * Phase 6.2 - Calcul des métriques stratégiques
 * 
 * RÈGLE D'OR : OBSERVE, ANALYSE, ANTICIPE
 */
class AdvancedKpiService
{
    /**
     * Calculer le taux de churn
     * 
     * Churn = (Abonnements annulés / Abonnements actifs début période) × 100
     * 
     * @param string|null $period 'month' ou 'year' (défaut: 'month')
     * @return float Taux de churn en pourcentage
     */
    public function calculateChurnRate(?string $period = 'month'): float
    {
        if ($period === 'year') {
            $startDate = now()->subYear()->startOfYear();
            $endDate = now()->subYear()->endOfYear();
        } else {
            $startDate = now()->subMonth()->startOfMonth();
            $endDate = now()->subMonth()->endOfMonth();
        }

        // Abonnements actifs au début de la période
        $activeAtStart = CreatorSubscription::whereIn('status', ['active', 'trialing'])
            ->where('started_at', '<=', $startDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $startDate);
            })
            ->count();

        if ($activeAtStart === 0) {
            return 0.0;
        }

        // Abonnements annulés pendant la période
        $canceledDuringPeriod = CreatorSubscription::where('status', 'canceled')
            ->whereBetween('canceled_at', [$startDate, $endDate])
            ->count();

        return round(($canceledDuringPeriod / $activeAtStart) * 100, 2);
    }

    /**
     * Calculer le LTV (Lifetime Value)
     * 
     * LTV = ARPU × Durée moyenne d'abonnement
     * 
     * @return float LTV en XAF
     */
    public function calculateLtv(): float
    {
        $arpu = $this->calculateArpu();
        $averageDuration = $this->calculateAverageSubscriptionDuration();

        return round($arpu * $averageDuration, 2);
    }

    /**
     * Calculer l'ARPU (Average Revenue Per User)
     * 
     * ARPU = Revenu total / Nombre de créateurs payants
     * 
     * @return float ARPU en XAF
     */
    public function calculateArpu(): float
    {
        // Revenu total (MRR actuel)
        $mrr = $this->calculateCurrentMRR();

        // Nombre de créateurs payants (abonnements actifs avec prix > 0)
        $payingCreators = CreatorSubscription::whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->whereHas('plan', function ($query) {
                $query->where('price', '>', 0);
            })
            ->count();

        if ($payingCreators === 0) {
            return 0.0;
        }

        return round($mrr / $payingCreators, 2);
    }

    /**
     * Calculer la durée moyenne d'abonnement
     * 
     * Durée moyenne = Moyenne des durées des abonnements annulés
     * 
     * @return float Durée moyenne en mois
     */
    public function calculateAverageSubscriptionDuration(): float
    {
        $canceledSubscriptions = CreatorSubscription::where('status', 'canceled')
            ->whereNotNull('started_at')
            ->whereNotNull('canceled_at')
            ->get();

        if ($canceledSubscriptions->isEmpty()) {
            // Si aucun abonnement annulé, utiliser la durée moyenne des abonnements actifs
            return $this->calculateAverageActiveSubscriptionDuration();
        }

        $totalMonths = 0;
        $count = 0;

        foreach ($canceledSubscriptions as $subscription) {
            $start = Carbon::parse($subscription->started_at);
            $end = Carbon::parse($subscription->canceled_at);
            $months = $start->diffInMonths($end);
            
            if ($months > 0) {
                $totalMonths += $months;
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        return round($totalMonths / $count, 2);
    }

    /**
     * Calculer la durée moyenne des abonnements actifs
     * 
     * @return float Durée moyenne en mois
     */
    private function calculateAverageActiveSubscriptionDuration(): float
    {
        $activeSubscriptions = CreatorSubscription::whereIn('status', ['active', 'trialing'])
            ->whereNotNull('started_at')
            ->get();

        if ($activeSubscriptions->isEmpty()) {
            return 0.0;
        }

        $totalMonths = 0;
        $count = 0;

        foreach ($activeSubscriptions as $subscription) {
            $start = Carbon::parse($subscription->started_at);
            $end = now();
            $months = $start->diffInMonths($end);
            
            if ($months > 0) {
                $totalMonths += $months;
                $count++;
            }
        }

        if ($count === 0) {
            return 0.0;
        }

        return round($totalMonths / $count, 2);
    }

    /**
     * Calculer le MRR actuel
     * 
     * @return float
     */
    private function calculateCurrentMRR(): float
    {
        $subscriptions = CreatorSubscription::whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->with('plan')
            ->get();

        $mrr = 0;
        foreach ($subscriptions as $subscription) {
            if ($subscription->plan && $subscription->plan->price > 0) {
                $mrr += (float) $subscription->plan->price;
            }
        }

        return $mrr;
    }
}




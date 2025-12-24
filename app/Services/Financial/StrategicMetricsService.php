<?php

namespace App\Services\Financial;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service de calcul des métriques stratégiques (BI)
 * 
 * Phase 6.2 - Métriques Stratégiques (BI)
 */
class StrategicMetricsService
{
    /**
     * Calculer le Churn Rate
     * 
     * Churn Rate = (abonnements annulés / abonnements actifs) × 100
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float Churn rate en pourcentage
     */
    public function calculateChurnRate(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        // Abonnements actifs au début du mois
        $activeAtStart = CreatorSubscription::where('status', 'active')
            ->where('started_at', '<=', $startOfMonth)
            ->where(function ($query) use ($startOfMonth) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $startOfMonth);
            })
            ->count();

        if ($activeAtStart === 0) {
            return 0;
        }

        // Abonnements annulés pendant le mois
        $canceledDuringMonth = CreatorSubscription::where('status', 'canceled')
            ->whereBetween('canceled_at', [$startOfMonth, $endOfMonth])
            ->count();

        return round(($canceledDuringMonth / $activeAtStart) * 100, 2);
    }

    /**
     * Calculer l'ARPU (Average Revenue Per User)
     * 
     * ARPU = revenu total / nombre de créateurs payants
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float ARPU en XAF
     */
    public function calculateARPU(?string $month = null): float
    {
        $month = $month ?? now()->format('Y-m');
        
        $financialService = app(FinancialDashboardService::class);
        $mrr = $financialService->calculateMRR($month);
        
        // Nombre de créateurs payants (avec abonnement actif payant)
        $payingCreators = CreatorProfile::whereHas('subscriptions', function ($query) {
            $query->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->whereHas('plan', function ($q) {
                    $q->where('price', '>', 0)
                        ->where('code', '!=', 'free');
                });
        })->count();

        if ($payingCreators === 0) {
            return 0;
        }

        return round($mrr / $payingCreators, 2);
    }

    /**
     * Calculer le LTV (Lifetime Value) créateur
     * 
     * LTV = ARPU × durée moyenne abonnement (en mois)
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return float LTV en XAF
     */
    public function calculateLTV(?string $month = null): float
    {
        $arpu = $this->calculateARPU($month);
        
        // Durée moyenne d'abonnement (en mois)
        $averageDuration = $this->calculateAverageSubscriptionDuration();

        return round($arpu * $averageDuration, 2);
    }

    /**
     * Calculer la durée moyenne d'abonnement (en mois)
     * 
     * @return float Durée moyenne en mois
     */
    public function calculateAverageSubscriptionDuration(): float
    {
        $subscriptions = CreatorSubscription::where('status', 'canceled')
            ->whereNotNull('canceled_at')
            ->whereNotNull('started_at')
            ->get();

        if ($subscriptions->isEmpty()) {
            // Si aucun abonnement annulé, utiliser une durée par défaut (ex: 6 mois)
            return 6.0;
        }

        $totalMonths = 0;
        $count = 0;

        foreach ($subscriptions as $subscription) {
            $duration = $subscription->started_at->diffInMonths($subscription->canceled_at);
            if ($duration > 0) {
                $totalMonths += $duration;
                $count++;
            }
        }

        if ($count === 0) {
            return 6.0; // Durée par défaut
        }

        return round($totalMonths / $count, 2);
    }

    /**
     * Calculer le taux d'activation créateur
     * 
     * Taux d'activation = (créateurs complete / créateurs inscrits) × 100
     * 
     * @return float Taux d'activation en pourcentage
     */
    public function calculateActivationRate(): float
    {
        // Créateurs inscrits (avec profil créé)
        $totalCreators = CreatorProfile::count();

        if ($totalCreators === 0) {
            return 0;
        }

        // Créateurs avec onboarding complet
        $activatedCreators = CreatorProfile::whereHas('stripeAccount', function ($query) {
            $query->where('onboarding_status', 'complete')
                ->where('charges_enabled', true)
                ->where('payouts_enabled', true);
        })->count();

        return round(($activatedCreators / $totalCreators) * 100, 2);
    }

    /**
     * Calculer le Stripe Health Score
     * 
     * Score composite basé sur :
     * - % comptes charges_enabled
     * - % comptes payouts_enabled
     * - % onboarding complet
     * 
     * @return array
     */
    public function calculateStripeHealthScore(): array
    {
        $totalAccounts = CreatorStripeAccount::count();

        if ($totalAccounts === 0) {
            return [
                'score' => 0,
                'charges_enabled_rate' => 0,
                'payouts_enabled_rate' => 0,
                'onboarding_complete_rate' => 0,
            ];
        }

        $chargesEnabled = CreatorStripeAccount::where('charges_enabled', true)->count();
        $payoutsEnabled = CreatorStripeAccount::where('payouts_enabled', true)->count();
        $onboardingComplete = CreatorStripeAccount::where('onboarding_status', 'complete')->count();

        $chargesRate = round(($chargesEnabled / $totalAccounts) * 100, 2);
        $payoutsRate = round(($payoutsEnabled / $totalAccounts) * 100, 2);
        $onboardingRate = round(($onboardingComplete / $totalAccounts) * 100, 2);

        // Score composite (moyenne pondérée)
        $score = round(($chargesRate + $payoutsRate + $onboardingRate) / 3, 2);

        return [
            'score' => $score,
            'charges_enabled_rate' => $chargesRate,
            'payouts_enabled_rate' => $payoutsRate,
            'onboarding_complete_rate' => $onboardingRate,
            'total_accounts' => $totalAccounts,
        ];
    }

    /**
     * Obtenir toutes les métriques stratégiques
     * 
     * @param string|null $month Mois au format 'YYYY-MM' (null = mois actuel)
     * @return array
     */
    public function getAllStrategicMetrics(?string $month = null): array
    {
        $month = $month ?? now()->format('Y-m');

        return [
            'month' => $month,
            'churn_rate' => $this->calculateChurnRate($month),
            'arpu' => $this->calculateARPU($month),
            'ltv' => $this->calculateLTV($month),
            'average_subscription_duration' => $this->calculateAverageSubscriptionDuration(),
            'activation_rate' => $this->calculateActivationRate(),
            'stripe_health_score' => $this->calculateStripeHealthScore(),
        ];
    }
}


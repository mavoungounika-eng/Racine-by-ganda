<?php

namespace App\Services\BI;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\CreatorSubscriptionInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service BI - Dashboard Financier Admin
 * 
 * Phase 6.1 - Calcul des KPI financiers et opérationnels
 * 
 * RÈGLE D'OR : OBSERVE, ANALYSE, ANTICIPE
 * Ne facture pas, ne modifie rien, ne déclenche rien
 */
class AdminFinancialDashboardService
{
    /**
     * Obtenir les métriques de revenus
     * 
     * @return array
     */
    public function getRevenueMetrics(): array
    {
        $currentMonth = now()->format('Y-m');
        $previousMonth = now()->subMonth()->format('Y-m');
        
        $mrr = $this->calculateMRR($currentMonth);
        $arr = $mrr * 12;
        $totalRevenue = $this->calculateTotalRevenue();
        $currentMonthRevenue = $this->calculateMonthRevenue($currentMonth);
        $previousMonthRevenue = $this->calculateMonthRevenue($previousMonth);
        
        $momVariation = $previousMonthRevenue > 0 
            ? round((($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 2)
            : 0;

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'total_revenue' => round($totalRevenue, 2),
            'current_month_revenue' => round($currentMonthRevenue, 2),
            'previous_month_revenue' => round($previousMonthRevenue, 2),
            'mom_variation_percent' => $momVariation,
        ];
    }

    /**
     * Obtenir les métriques d'abonnements
     * 
     * @return array
     */
    public function getSubscriptionMetrics(): array
    {
        return [
            'active' => $this->countSubscriptionsByStatus('active'),
            'trialing' => $this->countSubscriptionsByStatus('trialing'),
            'past_due' => $this->countSubscriptionsByStatus('past_due'),
            'unpaid' => $this->countSubscriptionsByStatus('unpaid'),
            'canceled' => $this->countSubscriptionsByStatus('canceled'),
            'total' => CreatorSubscription::count(),
        ];
    }

    /**
     * Obtenir les métriques de créateurs
     * 
     * @return array
     */
    public function getCreatorMetrics(): array
    {
        $totalCreators = CreatorProfile::count();
        $activeCreators = $this->countActiveCreators();
        $blockedCreators = $this->countBlockedCreators();
        $onboardingIncomplete = $this->countOnboardingIncomplete();
        $eligibleForPayments = $this->countEligibleForPayments();

        return [
            'total' => $totalCreators,
            'active' => $activeCreators,
            'blocked' => $blockedCreators,
            'onboarding_incomplete' => $onboardingIncomplete,
            'eligible_for_payments' => $eligibleForPayments,
        ];
    }

    /**
     * Obtenir les métriques de santé Stripe
     * 
     * @return array
     */
    public function getStripeHealthMetrics(): array
    {
        $totalAccounts = CreatorStripeAccount::count();
        
        if ($totalAccounts === 0) {
            return [
                'charges_enabled_percent' => 0,
                'payouts_enabled_percent' => 0,
                'onboarding_complete_percent' => 0,
                'failed_accounts' => 0,
                'total_accounts' => 0,
            ];
        }

        $chargesEnabled = CreatorStripeAccount::where('charges_enabled', true)->count();
        $payoutsEnabled = CreatorStripeAccount::where('payouts_enabled', true)->count();
        $onboardingComplete = CreatorStripeAccount::where('onboarding_status', 'complete')->count();
        $failedAccounts = CreatorStripeAccount::where('onboarding_status', 'failed')->count();

        return [
            'charges_enabled_percent' => round(($chargesEnabled / $totalAccounts) * 100, 2),
            'payouts_enabled_percent' => round(($payoutsEnabled / $totalAccounts) * 100, 2),
            'onboarding_complete_percent' => round(($onboardingComplete / $totalAccounts) * 100, 2),
            'failed_accounts' => $failedAccounts,
            'total_accounts' => $totalAccounts,
        ];
    }

    /**
     * Obtenir les métriques de risques
     * 
     * @return array
     */
    public function getRiskMetrics(): array
    {
        $pastDueCreators = $this->countCreatorsWithStatus('past_due');
        $unpaidCreators = $this->countCreatorsWithStatus('unpaid');
        $failedPayments7Days = $this->countFailedPaymentsLast7Days();
        $highRiskCreators = $this->countHighRiskCreators();

        return [
            'creators_past_due' => $pastDueCreators,
            'creators_unpaid' => $unpaidCreators,
            'failed_payments_7_days' => $failedPayments7Days,
            'high_risk_creators' => $highRiskCreators,
        ];
    }

    /**
     * Calculer le MRR (Monthly Recurring Revenue)
     * 
     * @param string $month Format 'YYYY-MM'
     * @return float
     */
    private function calculateMRR(string $month): float
    {
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
        
        $subscriptions = CreatorSubscription::whereIn('status', ['active', 'trialing'])
            ->where(function ($query) use ($endOfMonth) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $endOfMonth);
            })
            ->where('started_at', '<=', $endOfMonth)
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

    /**
     * Calculer le revenu total encaissé (toutes les factures payées)
     * 
     * @return float
     */
    private function calculateTotalRevenue(): float
    {
        return (float) CreatorSubscriptionInvoice::where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Calculer le revenu d'un mois spécifique
     * 
     * @param string $month Format 'YYYY-MM'
     * @return float
     */
    private function calculateMonthRevenue(string $month): float
    {
        $startOfMonth = Carbon::parse($month . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();

        return (float) CreatorSubscriptionInvoice::where('status', 'paid')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('amount');
    }

    /**
     * Compter les abonnements par statut
     * 
     * @param string $status
     * @return int
     */
    private function countSubscriptionsByStatus(string $status): int
    {
        return CreatorSubscription::where('status', $status)->count();
    }

    /**
     * Compter les créateurs actifs
     * 
     * @return int
     */
    private function countActiveCreators(): int
    {
        return CreatorProfile::where('is_active', true)
            ->where('status', 'active')
            ->count();
    }

    /**
     * Compter les créateurs bloqués
     * 
     * @return int
     */
    private function countBlockedCreators(): int
    {
        return CreatorProfile::where(function ($query) {
            $query->where('is_active', false)
                ->orWhere('status', 'suspended')
                ->orWhereHas('subscriptions', function ($q) {
                    $q->whereIn('status', ['unpaid', 'past_due']);
                });
        })->count();
    }

    /**
     * Compter les créateurs avec onboarding incomplet
     * 
     * @return int
     */
    private function countOnboardingIncomplete(): int
    {
        return CreatorProfile::whereHas('stripeAccount', function ($query) {
            $query->where('onboarding_status', '!=', 'complete')
                ->where('onboarding_status', '!=', 'failed');
        })->count();
    }

    /**
     * Compter les créateurs éligibles pour recevoir des paiements
     * 
     * @return int
     */
    private function countEligibleForPayments(): int
    {
        return CreatorProfile::where('is_active', true)
            ->where('status', 'active')
            ->whereHas('stripeAccount', function ($query) {
                $query->where('charges_enabled', true)
                    ->where('payouts_enabled', true)
                    ->where('onboarding_status', 'complete');
            })
            ->whereHas('subscriptions', function ($query) {
                $query->where('status', 'active');
            })
            ->count();
    }

    /**
     * Compter les créateurs avec un statut d'abonnement spécifique
     * 
     * @param string $status
     * @return int
     */
    private function countCreatorsWithStatus(string $status): int
    {
        return CreatorProfile::whereHas('subscriptions', function ($query) use ($status) {
            $query->where('status', $status);
        })->count();
    }

    /**
     * Compter les paiements échoués des 7 derniers jours
     * 
     * @return int
     */
    private function countFailedPaymentsLast7Days(): int
    {
        $sevenDaysAgo = now()->subDays(7);

        return CreatorSubscription::whereIn('status', ['unpaid', 'past_due'])
            ->where('updated_at', '>=', $sevenDaysAgo)
            ->count();
    }

    /**
     * Compter les créateurs à risque élevé
     * 
     * Un créateur est à risque élevé si :
     * - Abonnement unpaid ou past_due
     * - OU charges_enabled = false
     * - OU payouts_enabled = false
     * - OU onboarding incomplet depuis > 7 jours
     * 
     * @return int
     */
    private function countHighRiskCreators(): int
    {
        $sevenDaysAgo = now()->subDays(7);

        return CreatorProfile::where(function ($query) use ($sevenDaysAgo) {
            // Abonnement unpaid ou past_due
            $query->whereHas('subscriptions', function ($q) {
                $q->whereIn('status', ['unpaid', 'past_due']);
            })
            // OU charges/payouts désactivés
            ->orWhereHas('stripeAccount', function ($q) {
                $q->where('charges_enabled', false)
                    ->orWhere('payouts_enabled', false);
            })
            // OU onboarding incomplet depuis > 7 jours
            ->orWhereHas('stripeAccount', function ($q) use ($sevenDaysAgo) {
                $q->where('onboarding_status', '!=', 'complete')
                    ->where('onboarding_status', '!=', 'failed')
                    ->where('created_at', '<=', $sevenDaysAgo);
            });
        })->count();
    }
}




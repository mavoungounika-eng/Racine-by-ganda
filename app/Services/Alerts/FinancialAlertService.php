<?php

namespace App\Services\Alerts;

use App\Models\CreatorProfile;
use App\Services\BI\AdvancedKpiService;
use App\Services\BI\AdminFinancialDashboardService;
use Carbon\Carbon;

/**
 * Service d'alertes financières intelligentes
 * 
 * Phase 6.4 - Alertes automatiques
 * 
 * RÈGLE D'OR : OBSERVE, ANALYSE, ANTICIPE
 * Retourne des alertes structurées, ne les envoie pas
 */
class FinancialAlertService
{
    protected AdvancedKpiService $kpiService;
    protected AdminFinancialDashboardService $dashboardService;

    public function __construct(
        AdvancedKpiService $kpiService,
        AdminFinancialDashboardService $dashboardService
    ) {
        $this->kpiService = $kpiService;
        $this->dashboardService = $dashboardService;
    }

    /**
     * Vérifier les alertes globales
     * 
     * @return array
     */
    public function checkGlobalAlerts(): array
    {
        $alerts = [];

        // Alerte : Churn élevé
        $churnRate = $this->kpiService->calculateChurnRate('month');
        if ($churnRate > 10) {
            $alerts[] = [
                'type' => 'high_churn',
                'severity' => 'high',
                'message' => "Taux de churn élevé : {$churnRate}%",
                'value' => $churnRate,
                'threshold' => 10,
                'recommended_action' => 'Analyser les raisons d\'annulation',
            ];
        } elseif ($churnRate > 5) {
            $alerts[] = [
                'type' => 'high_churn',
                'severity' => 'medium',
                'message' => "Taux de churn modéré : {$churnRate}%",
                'value' => $churnRate,
                'threshold' => 5,
                'recommended_action' => 'Surveiller les tendances',
            ];
        }

        // Alerte : Revenus en baisse MoM
        $revenueMetrics = $this->dashboardService->getRevenueMetrics();
        if ($revenueMetrics['mom_variation_percent'] < -10) {
            $alerts[] = [
                'type' => 'revenue_decline',
                'severity' => 'high',
                'message' => "Revenus en baisse : {$revenueMetrics['mom_variation_percent']}%",
                'value' => $revenueMetrics['mom_variation_percent'],
                'threshold' => -10,
                'recommended_action' => 'Analyser les causes de la baisse',
            ];
        } elseif ($revenueMetrics['mom_variation_percent'] < -5) {
            $alerts[] = [
                'type' => 'revenue_decline',
                'severity' => 'medium',
                'message' => "Revenus en légère baisse : {$revenueMetrics['mom_variation_percent']}%",
                'value' => $revenueMetrics['mom_variation_percent'],
                'threshold' => -5,
                'recommended_action' => 'Surveiller les tendances',
            ];
        }

        // Alerte : Trop de créateurs unpaid
        $riskMetrics = $this->dashboardService->getRiskMetrics();
        $subscriptionMetrics = $this->dashboardService->getSubscriptionMetrics();
        $totalActive = $subscriptionMetrics['active'] + $subscriptionMetrics['trialing'];
        
        if ($totalActive > 0) {
            $unpaidPercentage = ($riskMetrics['creators_unpaid'] / $totalActive) * 100;
            if ($unpaidPercentage > 15) {
                $alerts[] = [
                    'type' => 'high_unpaid_creators',
                    'severity' => 'high',
                    'message' => "Trop de créateurs unpaid : {$riskMetrics['creators_unpaid']} ({$unpaidPercentage}%)",
                    'value' => $unpaidPercentage,
                    'threshold' => 15,
                    'recommended_action' => 'Relancer les créateurs unpaid',
                ];
            }
        }

        // Alerte : Trop de paiements échoués
        if ($riskMetrics['failed_payments_7_days'] > 10) {
            $alerts[] = [
                'type' => 'high_failed_payments',
                'severity' => 'high',
                'message' => "Nombre élevé de paiements échoués (7j) : {$riskMetrics['failed_payments_7_days']}",
                'value' => $riskMetrics['failed_payments_7_days'],
                'threshold' => 10,
                'recommended_action' => 'Vérifier les problèmes de paiement',
            ];
        } elseif ($riskMetrics['failed_payments_7_days'] > 5) {
            $alerts[] = [
                'type' => 'high_failed_payments',
                'severity' => 'medium',
                'message' => "Paiements échoués modérés (7j) : {$riskMetrics['failed_payments_7_days']}",
                'value' => $riskMetrics['failed_payments_7_days'],
                'threshold' => 5,
                'recommended_action' => 'Surveiller les tendances',
            ];
        }

        return $alerts;
    }

    /**
     * Vérifier les alertes pour un créateur spécifique
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    public function checkCreatorAlerts(CreatorProfile $creator): array
    {
        $alerts = [];

        // Vérifier l'abonnement
        $subscription = $creator->subscriptions()->latest()->first();
        
        if ($subscription) {
            // Alerte : Passage en unpaid
            if ($subscription->status === 'unpaid') {
                $alerts[] = [
                    'type' => 'subscription_unpaid',
                    'severity' => 'high',
                    'message' => 'Abonnement unpaid - Créateur doit être suspendu',
                    'subscription_id' => $subscription->id,
                    'recommended_action' => 'Suspendre le créateur',
                ];
            }

            // Alerte : Passage en past_due
            if ($subscription->status === 'past_due') {
                $alerts[] = [
                    'type' => 'subscription_past_due',
                    'severity' => 'medium',
                    'message' => 'Abonnement past_due - Paiement en retard',
                    'subscription_id' => $subscription->id,
                    'recommended_action' => 'Relancer le créateur',
                ];
            }
        }

        // Vérifier le compte Stripe
        $stripeAccount = $creator->stripeAccount;
        
        if ($stripeAccount) {
            // Alerte : Désactivation charges
            if (!$stripeAccount->charges_enabled) {
                $alerts[] = [
                    'type' => 'stripe_charges_disabled',
                    'severity' => 'high',
                    'message' => 'Charges désactivés sur Stripe',
                    'stripe_account_id' => $stripeAccount->stripe_account_id,
                    'recommended_action' => 'Vérifier le compte Stripe',
                ];
            }

            // Alerte : Désactivation payouts
            if (!$stripeAccount->payouts_enabled) {
                $alerts[] = [
                    'type' => 'stripe_payouts_disabled',
                    'severity' => 'high',
                    'message' => 'Payouts désactivés sur Stripe',
                    'stripe_account_id' => $stripeAccount->stripe_account_id,
                    'recommended_action' => 'Vérifier le compte Stripe',
                ];
            }

            // Alerte : Onboarding incomplet depuis > 7 jours
            if ($stripeAccount->onboarding_status !== 'complete' 
                && $stripeAccount->created_at 
                && $stripeAccount->created_at->lt(now()->subDays(7))) {
                $alerts[] = [
                    'type' => 'onboarding_incomplete',
                    'severity' => 'medium',
                    'message' => 'Onboarding Stripe incomplet depuis plus de 7 jours',
                    'stripe_account_id' => $stripeAccount->stripe_account_id,
                    'onboarding_status' => $stripeAccount->onboarding_status,
                    'recommended_action' => 'Relancer l\'onboarding',
                ];
            }
        } else {
            // Alerte : Pas de compte Stripe
            $alerts[] = [
                'type' => 'no_stripe_account',
                'severity' => 'medium',
                'message' => 'Aucun compte Stripe associé',
                'recommended_action' => 'Créer un compte Stripe',
            ];
        }

        // Vérifier l'éligibilité paiements
        if (!$this->isEligibleForPayments($creator)) {
            $alerts[] = [
                'type' => 'not_eligible_payments',
                'severity' => 'high',
                'message' => 'Créateur non éligible pour recevoir des paiements',
                'recommended_action' => 'Vérifier les conditions d\'éligibilité',
            ];
        }

        return $alerts;
    }

    /**
     * Vérifier si un créateur est éligible pour recevoir des paiements
     * 
     * @param CreatorProfile $creator
     * @return bool
     */
    private function isEligibleForPayments(CreatorProfile $creator): bool
    {
        if (!$creator->is_active || $creator->status !== 'active') {
            return false;
        }

        $stripeAccount = $creator->stripeAccount;
        if (!$stripeAccount) {
            return false;
        }

        if (!$stripeAccount->charges_enabled || !$stripeAccount->payouts_enabled) {
            return false;
        }

        if ($stripeAccount->onboarding_status !== 'complete') {
            return false;
        }

        $subscription = $creator->subscriptions()->latest()->first();
        if (!$subscription || $subscription->status !== 'active') {
            return false;
        }

        return true;
    }
}




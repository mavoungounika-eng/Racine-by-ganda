<?php

namespace App\Services\Risk;

use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use Carbon\Carbon;

/**
 * Service d'évaluation des risques créateurs
 * 
 * Phase 6.3 - Détection automatique des risques
 * 
 * RÈGLE D'OR : OBSERVE, ANALYSE, ANTICIPE
 */
class CreatorRiskAssessmentService
{
    /**
     * Évaluer le risque d'un créateur
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    public function assessCreatorRisk(CreatorProfile $creator): array
    {
        $reasons = [];
        $riskScore = 0;

        // Vérifier l'abonnement
        $subscription = $creator->subscriptions()->latest()->first();
        
        if ($subscription) {
            if (in_array($subscription->status, ['unpaid', 'past_due'])) {
                $reasons[] = 'Abonnement ' . $subscription->status;
                $riskScore += 40;
            }
        } else {
            $reasons[] = 'Aucun abonnement actif';
            $riskScore += 30;
        }

        // Vérifier le compte Stripe
        $stripeAccount = $creator->stripeAccount;
        
        if ($stripeAccount) {
            if (!$stripeAccount->charges_enabled) {
                $reasons[] = 'Charges désactivés sur Stripe';
                $riskScore += 20;
            }

            if (!$stripeAccount->payouts_enabled) {
                $reasons[] = 'Payouts désactivés sur Stripe';
                $riskScore += 20;
            }

            if ($stripeAccount->onboarding_status !== 'complete') {
                $reasons[] = 'Onboarding Stripe incomplet (' . $stripeAccount->onboarding_status . ')';
                
                // Si onboarding incomplet depuis > 7 jours, risque plus élevé
                if ($stripeAccount->created_at && $stripeAccount->created_at->lt(now()->subDays(7))) {
                    $riskScore += 15;
                } else {
                    $riskScore += 10;
                }
            }
        } else {
            $reasons[] = 'Aucun compte Stripe';
            $riskScore += 25;
        }

        // Vérifier les paiements échoués récurrents
        if ($subscription) {
            $failedPaymentsCount = $this->countFailedPayments($creator, 30);
            if ($failedPaymentsCount >= 3) {
                $reasons[] = "{$failedPaymentsCount} paiements échoués dans les 30 derniers jours";
                $riskScore += 15;
            } elseif ($failedPaymentsCount >= 1) {
                $reasons[] = "{$failedPaymentsCount} paiement(s) échoué(s) récent(s)";
                $riskScore += 5;
            }
        }

        // Déterminer le niveau de risque
        if ($riskScore >= 60) {
            $riskLevel = 'high';
            $recommendedAction = 'suspend';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'medium';
            $recommendedAction = 'notify';
        } else {
            $riskLevel = 'low';
            $recommendedAction = 'monitor';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => $riskScore,
            'reasons' => $reasons,
            'recommended_action' => $recommendedAction,
            'assessed_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Compter les paiements échoués d'un créateur
     * 
     * @param CreatorProfile $creator
     * @param int $days Nombre de jours à analyser
     * @return int
     */
    private function countFailedPayments(CreatorProfile $creator, int $days = 30): int
    {
        $startDate = now()->subDays($days);

        return CreatorSubscription::where('creator_profile_id', $creator->id)
            ->whereIn('status', ['unpaid', 'past_due'])
            ->where('updated_at', '>=', $startDate)
            ->count();
    }
}




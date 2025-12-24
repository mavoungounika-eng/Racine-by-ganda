<?php

namespace App\Services\Decision;

use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use Carbon\Carbon;

/**
 * Service de Prédiction de Churn (Rule-Based)
 * 
 * Phase 7.2 - Estimation probabiliste du churn
 * 
 * RÈGLE D'OR : OBSERVE, COMPREND, RECOMMANDE
 * Aucun ML opaque, règles métier justifiables
 */
class ChurnPredictionService
{
    /**
     * Prédire le risque de churn d'un créateur
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    public function predictChurn(CreatorProfile $creator): array
    {
        $riskScore = 0;
        $maxRiskScore = 100;
        $factors = [];

        // Facteur 1 : Statut de l'abonnement (30 points)
        $subscription = $creator->subscriptions()->latest()->first();
        if ($subscription) {
            if ($subscription->status === 'unpaid') {
                $riskScore += 30;
                $factors[] = 'Abonnement unpaid (risque critique)';
            } elseif ($subscription->status === 'past_due') {
                $riskScore += 20;
                $factors[] = 'Abonnement past_due (paiement en retard)';
            } elseif ($subscription->status === 'canceled') {
                $riskScore += 25;
                $factors[] = 'Abonnement annulé';
            } elseif ($subscription->cancel_at_period_end) {
                $riskScore += 15;
                $factors[] = 'Annulation programmée à la fin de période';
            }
        } else {
            $riskScore += 25;
            $factors[] = 'Aucun abonnement actif';
        }

        // Facteur 2 : Historique des paiements (25 points)
        if ($subscription) {
            $failedPayments = $this->countFailedPayments($creator, 90);
            if ($failedPayments >= 3) {
                $riskScore += 25;
                $factors[] = "{$failedPayments} paiements échoués dans les 90 derniers jours";
            } elseif ($failedPayments >= 2) {
                $riskScore += 15;
                $factors[] = "{$failedPayments} paiements échoués récents";
            } elseif ($failedPayments >= 1) {
                $riskScore += 8;
                $factors[] = "1 paiement échoué récent";
            }
        }

        // Facteur 3 : Durée de l'abonnement (20 points)
        if ($subscription && $subscription->started_at) {
            $months = now()->diffInMonths($subscription->started_at);
            // Plus l'abonnement est récent, plus le risque est élevé
            if ($months < 1) {
                $riskScore += 20;
                $factors[] = 'Abonnement très récent (< 1 mois)';
            } elseif ($months < 3) {
                $riskScore += 15;
                $factors[] = 'Abonnement récent (< 3 mois)';
            } elseif ($months >= 12) {
                // Abonnement ancien = risque réduit
                $riskScore -= 10;
                $factors[] = 'Abonnement établi (≥ 12 mois) - risque réduit';
            }
        }

        // Facteur 4 : Engagement (15 points)
        $productsCount = $creator->products()->where('is_active', true)->count();
        if ($productsCount === 0) {
            $riskScore += 15;
            $factors[] = 'Aucun produit actif';
        } elseif ($productsCount < 3) {
            $riskScore += 8;
            $factors[] = 'Peu de produits actifs';
        }

        // Facteur 5 : Problèmes Stripe (10 points)
        $stripeAccount = $creator->stripeAccount;
        if ($stripeAccount) {
            if (!$stripeAccount->charges_enabled || !$stripeAccount->payouts_enabled) {
                $riskScore += 10;
                $factors[] = 'Problèmes avec le compte Stripe';
            }
            if ($stripeAccount->onboarding_status === 'failed') {
                $riskScore += 8;
                $factors[] = 'Onboarding Stripe échoué';
            }
        } else {
            $riskScore += 5;
            $factors[] = 'Aucun compte Stripe';
        }

        // Normaliser le score (0-100)
        $riskScore = max(0, min($maxRiskScore, $riskScore));

        // Probabilité de churn (basée sur le score de risque)
        $churnProbability = $this->calculateChurnProbability($riskScore);

        // Classification
        $classification = $this->classifyChurnRisk($riskScore);

        return [
            'churn_probability' => round($churnProbability, 2),
            'risk_score' => round($riskScore, 2),
            'classification' => $classification,
            'factors' => $factors,
            'predicted_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculer la probabilité de churn (0-100%)
     * 
     * @param float $riskScore
     * @return float
     */
    private function calculateChurnProbability(float $riskScore): float
    {
        // Conversion non-linéaire : risque élevé = probabilité élevée
        // Formule : probabilité = (risque^1.2) / 100
        $probability = pow($riskScore / 100, 1.2) * 100;
        
        return min(100, max(0, round($probability, 2)));
    }

    /**
     * Classifier le risque de churn
     * 
     * @param float $riskScore
     * @return string
     */
    private function classifyChurnRisk(float $riskScore): string
    {
        if ($riskScore >= 70) {
            return 'high';
        } elseif ($riskScore >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Compter les paiements échoués
     * 
     * @param CreatorProfile $creator
     * @param int $days
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




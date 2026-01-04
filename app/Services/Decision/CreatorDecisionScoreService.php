<?php

namespace App\Services\Decision;

use App\Models\CreatorProfile;
use App\Services\BI\AdvancedKpiService;
use App\Services\Risk\CreatorRiskAssessmentService;

/**
 * Service de Scoring Décisionnel Créateur
 * 
 * Phase 7.1 - Score global (0-100) avec notation qualitative
 * 
 * RÈGLE D'OR : OBSERVE, COMPREND, RECOMMANDE
 * Aucune écriture DB, aucun déclenchement automatique
 */
class CreatorDecisionScoreService extends BaseDecisionService
{
    protected AdvancedKpiService $kpiService;
    protected CreatorRiskAssessmentService $riskService;

    public function __construct(
        AdvancedKpiService $kpiService,
        CreatorRiskAssessmentService $riskService
    ) {
        $this->kpiService = $kpiService;
        $this->riskService = $riskService;
    }

    /**
     * Nom du module pour la gouvernance
     */
    protected function getModuleName(): string
    {
        return 'creator_scoring';
    }

    /**
     * Calculer le score décisionnel global d'un créateur
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    public function calculateDecisionScore(CreatorProfile $creator): array
    {
        // Vérifier le cache (1h)
        $cacheKey = "decision_score_{$creator->id}";
        $cached = $this->getCachedResult($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Calcul avec logging automatique
        $result = $this->executeCalculation(
            'calculate_decision_score',
            ['creator_id' => $creator->id],
            function($input) use ($creator) {
                return $this->performScoreCalculation($creator);
            }
        );

        // Cache 1h
        if ($result !== null) {
            $this->cacheResult($cacheKey, $result, 3600);
        }

        return $result ?? [];
    }

    /**
     * Effectue le calcul du score (logique existante)
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    private function performScoreCalculation(CreatorProfile $creator): array
    {
        // Récupérer les pondérations depuis la config
        $weights = config('ai_decisional.weights.creator_scoring', [
            'financial_health' => 0.30,
            'operational_health' => 0.25,
            'engagement_level' => 0.20,
            'growth_potential' => 0.15,
            'risk_factor' => 0.10,
        ]);

        // Composantes du score (pondérées)
        $financialHealth = $this->calculateFinancialHealth($creator); // 30%
        $operationalHealth = $this->calculateOperationalHealth($creator); // 25%
        $engagementLevel = $this->calculateEngagementLevel($creator); // 20%
        $growthPotential = $this->calculateGrowthPotential($creator); // 15%
        $riskFactor = $this->calculateRiskFactor($creator); // 10% (inverse)

        // Score global pondéré (utilise les poids configurables)
        $globalScore = (
            ($financialHealth * $weights['financial_health']) +
            ($operationalHealth * $weights['operational_health']) +
            ($engagementLevel * $weights['engagement_level']) +
            ($growthPotential * $weights['growth_potential']) +
            ((100 - $riskFactor) * $weights['risk_factor'])
        );

        $globalScore = max(0, min(100, round($globalScore, 2)));

        // Notation qualitative
        $qualitativeGrade = $this->getQualitativeGrade($globalScore);

        // Forces et faiblesses
        $strengths = $this->identifyStrengths($creator, [
            'financial' => $financialHealth,
            'operational' => $operationalHealth,
            'engagement' => $engagementLevel,
            'growth' => $growthPotential,
            'risk' => $riskFactor,
        ]);

        $weaknesses = $this->identifyWeaknesses($creator, [
            'financial' => $financialHealth,
            'operational' => $operationalHealth,
            'engagement' => $engagementLevel,
            'growth' => $growthPotential,
            'risk' => $riskFactor,
        ]);

        // Niveau de confiance (basé sur la complétude des données)
        $confidenceLevel = $this->calculateConfidenceLevel($creator);

        return [
            'global_score' => $globalScore,
            'qualitative_grade' => $qualitativeGrade,
            'components' => [
                'financial_health' => round($financialHealth, 2),
                'operational_health' => round($operationalHealth, 2),
                'engagement_level' => round($engagementLevel, 2),
                'growth_potential' => round($growthPotential, 2),
                'risk_factor' => round($riskFactor, 2),
            ],
            'strengths' => $strengths,
            'weaknesses' => $weaknesses,
            'confidence_level' => $confidenceLevel,
            'calculated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Santé financière (0-100)
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateFinancialHealth(CreatorProfile $creator): float
    {
        $score = 0;
        $maxScore = 100;

        // Abonnement actif et payé (40 points)
        $subscription = $creator->subscriptions()->latest()->first();
        if ($subscription && $subscription->status === 'active') {
            $score += 40;
        } elseif ($subscription && $subscription->status === 'trialing') {
            $score += 30;
        } elseif ($subscription && $subscription->status === 'past_due') {
            $score += 10;
        }

        // Compte Stripe fonctionnel (30 points)
        $stripeAccount = $creator->stripeAccount;
        if ($stripeAccount) {
            if ($stripeAccount->charges_enabled && $stripeAccount->payouts_enabled) {
                $score += 30;
            } elseif ($stripeAccount->charges_enabled || $stripeAccount->payouts_enabled) {
                $score += 15;
            }
        }

        // Ancienneté abonnement (20 points)
        if ($subscription && $subscription->started_at) {
            $months = now()->diffInMonths($subscription->started_at);
            if ($months >= 12) {
                $score += 20;
            } elseif ($months >= 6) {
                $score += 15;
            } elseif ($months >= 3) {
                $score += 10;
            } elseif ($months >= 1) {
                $score += 5;
            }
        }

        // Absence d'échecs de paiement (10 points)
        if ($subscription) {
            $failedPayments = $this->countFailedPayments($creator, 90);
            if ($failedPayments === 0) {
                $score += 10;
            } elseif ($failedPayments === 1) {
                $score += 5;
            }
        }

        return min($score, $maxScore);
    }

    /**
     * Santé opérationnelle (0-100)
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateOperationalHealth(CreatorProfile $creator): float
    {
        $score = 0;
        $maxScore = 100;

        // Profil actif et vérifié (30 points)
        if ($creator->is_active && $creator->status === 'active') {
            $score += 30;
        } elseif ($creator->status === 'pending') {
            $score += 10;
        }

        // Onboarding Stripe complet (25 points)
        $stripeAccount = $creator->stripeAccount;
        if ($stripeAccount && $stripeAccount->onboarding_status === 'complete') {
            $score += 25;
        } elseif ($stripeAccount && $stripeAccount->onboarding_status === 'in_progress') {
            $score += 10;
        }

        // Complétude du profil (25 points)
        $completeness = $this->calculateProfileCompleteness($creator);
        $score += ($completeness / 100) * 25;

        // Documents vérifiés (20 points)
        $documentsCount = $creator->documents()->count();
        $verifiedDocuments = $creator->documents()->where('is_verified', true)->count();
        if ($documentsCount > 0) {
            $score += (($verifiedDocuments / $documentsCount) * 20);
        }

        return min($score, $maxScore);
    }

    /**
     * Niveau d'engagement (0-100)
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateEngagementLevel(CreatorProfile $creator): float
    {
        $score = 0;
        $maxScore = 100;

        // Nombre de produits actifs (40 points)
        $productsCount = $creator->products()->where('is_active', true)->count();
        if ($productsCount >= 20) {
            $score += 40;
        } elseif ($productsCount >= 10) {
            $score += 30;
        } elseif ($productsCount >= 5) {
            $score += 20;
        } elseif ($productsCount >= 1) {
            $score += 10;
        }

        // Utilisation récente (30 points)
        // Simulé : si le créateur a une activité récente (abonnement actif = activité)
        $subscription = $creator->subscriptions()->latest()->first();
        if ($subscription && $subscription->status === 'active') {
            $score += 30;
        } elseif ($subscription && $subscription->status === 'trialing') {
            $score += 20;
        }

        // Collections créées (20 points)
        $collectionsCount = $creator->collections()->count();
        if ($collectionsCount >= 5) {
            $score += 20;
        } elseif ($collectionsCount >= 2) {
            $score += 15;
        } elseif ($collectionsCount >= 1) {
            $score += 10;
        }

        // Vérification du profil (10 points)
        if ($creator->is_verified) {
            $score += 10;
        }

        return min($score, $maxScore);
    }

    /**
     * Potentiel de croissance (0-100)
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateGrowthPotential(CreatorProfile $creator): float
    {
        $score = 0;
        $maxScore = 100;

        // Plan actuel (30 points)
        $subscription = $creator->subscriptions()->latest()->first();
        if ($subscription && $subscription->plan) {
            $planCode = $subscription->plan->code;
            if ($planCode === 'premium') {
                $score += 30;
            } elseif ($planCode === 'official') {
                $score += 20;
            } elseif ($planCode === 'free') {
                $score += 10;
            }
        }

        // Trajectoire (40 points)
        // Si le créateur a un abonnement actif depuis plusieurs mois, potentiel élevé
        if ($subscription && $subscription->started_at) {
            $months = now()->diffInMonths($subscription->started_at);
            if ($months >= 6) {
                $score += 40;
            } elseif ($months >= 3) {
                $score += 30;
            } elseif ($months >= 1) {
                $score += 20;
            }
        }

        // Qualité du profil (30 points)
        $completeness = $this->calculateProfileCompleteness($creator);
        $score += ($completeness / 100) * 30;

        return min($score, $maxScore);
    }

    /**
     * Facteur de risque (0-100, plus élevé = plus risqué)
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateRiskFactor(CreatorProfile $creator): float
    {
        $riskAssessment = $this->riskService->assessCreatorRisk($creator);
        return (float) $riskAssessment['risk_score'];
    }

    /**
     * Obtenir la notation qualitative
     * 
     * @param float $score
     * @return string
     */
    private function getQualitativeGrade(float $score): string
    {
        if ($score >= 85) {
            return 'A';
        } elseif ($score >= 70) {
            return 'B';
        } elseif ($score >= 50) {
            return 'C';
        } else {
            return 'D';
        }
    }

    /**
     * Identifier les forces
     * 
     * @param CreatorProfile $creator
     * @param array $components
     * @return array
     */
    private function identifyStrengths(CreatorProfile $creator, array $components): array
    {
        $strengths = [];

        if ($components['financial'] >= 80) {
            $strengths[] = 'Santé financière excellente';
        }
        if ($components['operational'] >= 80) {
            $strengths[] = 'Opérations solides';
        }
        if ($components['engagement'] >= 80) {
            $strengths[] = 'Engagement élevé';
        }
        if ($components['growth'] >= 80) {
            $strengths[] = 'Potentiel de croissance élevé';
        }
        if ($components['risk'] <= 20) {
            $strengths[] = 'Risque faible';
        }

        if (empty($strengths)) {
            $strengths[] = 'Aucune force significative identifiée';
        }

        return $strengths;
    }

    /**
     * Identifier les faiblesses
     * 
     * @param CreatorProfile $creator
     * @param array $components
     * @return array
     */
    private function identifyWeaknesses(CreatorProfile $creator, array $components): array
    {
        $weaknesses = [];

        if ($components['financial'] < 50) {
            $weaknesses[] = 'Santé financière fragile';
        }
        if ($components['operational'] < 50) {
            $weaknesses[] = 'Opérations incomplètes';
        }
        if ($components['engagement'] < 50) {
            $weaknesses[] = 'Engagement faible';
        }
        if ($components['growth'] < 50) {
            $weaknesses[] = 'Potentiel de croissance limité';
        }
        if ($components['risk'] >= 60) {
            $weaknesses[] = 'Risque élevé';
        }

        if (empty($weaknesses)) {
            $weaknesses[] = 'Aucune faiblesse significative identifiée';
        }

        return $weaknesses;
    }

    /**
     * Calculer le niveau de confiance (0-100)
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateConfidenceLevel(CreatorProfile $creator): float
    {
        $confidence = 0;
        $maxConfidence = 100;

        // Données de base présentes (40 points)
        if ($creator->subscriptions()->exists()) {
            $confidence += 20;
        }
        if ($creator->stripeAccount) {
            $confidence += 20;
        }

        // Ancienneté (30 points)
        $subscription = $creator->subscriptions()->latest()->first();
        if ($subscription && $subscription->started_at) {
            $months = now()->diffInMonths($subscription->started_at);
            if ($months >= 6) {
                $confidence += 30;
            } elseif ($months >= 3) {
                $confidence += 20;
            } elseif ($months >= 1) {
                $confidence += 10;
            }
        }

        // Complétude des données (30 points)
        $completeness = $this->calculateProfileCompleteness($creator);
        $confidence += ($completeness / 100) * 30;

        return min($confidence, $maxConfidence);
    }

    /**
     * Calculer la complétude du profil
     * 
     * @param CreatorProfile $creator
     * @return float
     */
    private function calculateProfileCompleteness(CreatorProfile $creator): float
    {
        $fields = [
            'brand_name' => 15,
            'bio' => 10,
            'location' => 10,
            'logo_path' => 15,
            'banner_path' => 10,
            'website' => 5,
            'instagram_url' => 5,
            'legal_status' => 10,
            'payout_method' => 10,
            'payout_details' => 10,
        ];

        $total = 0;
        $max = 0;

        foreach ($fields as $field => $weight) {
            $max += $weight;
            if (!empty($creator->$field)) {
                $total += $weight;
            }
        }

        return $max > 0 ? ($total / $max) * 100 : 0;
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

        return \App\Models\CreatorSubscription::where('creator_profile_id', $creator->id)
            ->whereIn('status', ['unpaid', 'past_due'])
            ->where('updated_at', '>=', $startDate)
            ->count();
    }
}




<?php

namespace App\Services\Decision;

use App\Models\CreatorProfile;
use App\Services\Alerts\FinancialAlertService;
use App\Services\Risk\CreatorRiskAssessmentService;

/**
 * Moteur de Recommandations
 * 
 * Phase 7.3 - Actions recommandées avec justification métier
 * 
 * RÈGLE D'OR : OBSERVE, COMPREND, RECOMMANDE
 * Zéro déclenchement automatique
 */
class RecommendationEngineService
{
    protected CreatorRiskAssessmentService $riskService;
    protected FinancialAlertService $alertService;
    protected CreatorDecisionScoreService $decisionScoreService;
    protected ChurnPredictionService $churnPredictionService;

    public function __construct(
        CreatorRiskAssessmentService $riskService,
        FinancialAlertService $alertService
    ) {
        $this->riskService = $riskService;
        $this->alertService = $alertService;
        // Services créés à la demande pour éviter dépendances circulaires
        $this->decisionScoreService = app(CreatorDecisionScoreService::class);
        $this->churnPredictionService = app(ChurnPredictionService::class);
    }

    /**
     * Générer les recommandations pour un créateur
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    public function generateRecommendations(CreatorProfile $creator): array
    {
        $recommendations = [];

        // Analyser le risque
        $riskAssessment = $this->riskService->assessCreatorRisk($creator);
        
        // Analyser les alertes
        $alerts = $this->alertService->checkCreatorAlerts($creator);
        
        // Analyser le score décisionnel
        $decisionScore = $this->decisionScoreService->calculateDecisionScore($creator);
        
        // Analyser le churn
        $churnPrediction = $this->churnPredictionService->predictChurn($creator);

        // Recommandation 1 : Basée sur le risque
        $riskRecommendation = $this->getRiskBasedRecommendation($riskAssessment);
        if ($riskRecommendation) {
            $recommendations[] = $riskRecommendation;
        }

        // Recommandation 2 : Basée sur les alertes
        $alertRecommendations = $this->getAlertBasedRecommendations($alerts);
        $recommendations = array_merge($recommendations, $alertRecommendations);

        // Recommandation 3 : Basée sur le score décisionnel
        $scoreRecommendation = $this->getScoreBasedRecommendation($decisionScore);
        if ($scoreRecommendation) {
            $recommendations[] = $scoreRecommendation;
        }

        // Recommandation 4 : Basée sur le churn
        $churnRecommendation = $this->getChurnBasedRecommendation($churnPrediction);
        if ($churnRecommendation) {
            $recommendations[] = $churnRecommendation;
        }

        // Recommandation 5 : Basée sur l'amélioration
        $improvementRecommendations = $this->getImprovementRecommendations($creator, $decisionScore);
        $recommendations = array_merge($recommendations, $improvementRecommendations);

        // Trier par priorité
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            return ($priorityOrder[$b['priority']] ?? 0) - ($priorityOrder[$a['priority']] ?? 0);
        });

        return [
            'recommendations' => $recommendations,
            'total_count' => count($recommendations),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Recommandation basée sur le risque
     * 
     * @param array $riskAssessment
     * @return array|null
     */
    private function getRiskBasedRecommendation(array $riskAssessment): ?array
    {
        $riskLevel = $riskAssessment['risk_level'];
        $recommendedAction = $riskAssessment['recommended_action'];

        $actions = [
            'suspend' => [
                'action' => 'Suspendre le créateur',
                'priority' => 'critical',
                'justification' => 'Risque élevé détecté. Le créateur doit être suspendu pour protéger la plateforme.',
            ],
            'notify' => [
                'action' => 'Notifier et relancer le créateur',
                'priority' => 'high',
                'justification' => 'Risque modéré détecté. Une intervention proactive est recommandée.',
            ],
            'monitor' => [
                'action' => 'Surveiller le créateur',
                'priority' => 'medium',
                'justification' => 'Risque faible mais nécessite une surveillance continue.',
            ],
        ];

        if (isset($actions[$recommendedAction])) {
            return [
                'type' => 'risk_based',
                'action' => $actions[$recommendedAction]['action'],
                'priority' => $actions[$recommendedAction]['priority'],
                'justification' => $actions[$recommendedAction]['justification'],
                'details' => [
                    'risk_level' => $riskLevel,
                    'risk_score' => $riskAssessment['risk_score'],
                    'reasons' => $riskAssessment['reasons'],
                ],
            ];
        }

        return null;
    }

    /**
     * Recommandations basées sur les alertes
     * 
     * @param array $alerts
     * @return array
     */
    private function getAlertBasedRecommendations(array $alerts): array
    {
        $recommendations = [];

        foreach ($alerts as $alert) {
            $recommendations[] = [
                'type' => 'alert_based',
                'action' => $alert['recommended_action'],
                'priority' => $alert['severity'],
                'justification' => $alert['message'],
                'details' => [
                    'alert_type' => $alert['type'],
                    'alert_severity' => $alert['severity'],
                ],
            ];
        }

        return $recommendations;
    }

    /**
     * Recommandation basée sur le score décisionnel
     * 
     * @param array $decisionScore
     * @return array|null
     */
    private function getScoreBasedRecommendation(array $decisionScore): ?array
    {
        $globalScore = $decisionScore['global_score'];
        $grade = $decisionScore['qualitative_grade'];

        if ($globalScore >= 85) {
            return [
                'type' => 'score_based',
                'action' => 'Proposer upgrade PREMIUM',
                'priority' => 'low',
                'justification' => "Score excellent ({$grade}). Le créateur est prêt pour un upgrade PREMIUM.",
                'details' => [
                    'score' => $globalScore,
                    'grade' => $grade,
                ],
            ];
        } elseif ($globalScore < 50) {
            return [
                'type' => 'score_based',
                'action' => 'Accompagner le créateur',
                'priority' => 'high',
                'justification' => "Score faible ({$grade}). Un accompagnement personnalisé est nécessaire.",
                'details' => [
                    'score' => $globalScore,
                    'grade' => $grade,
                    'weaknesses' => $decisionScore['weaknesses'],
                ],
            ];
        }

        return null;
    }

    /**
     * Recommandation basée sur le churn
     * 
     * @param array $churnPrediction
     * @return array|null
     */
    private function getChurnBasedRecommendation(array $churnPrediction): ?array
    {
        $classification = $churnPrediction['classification'];
        $probability = $churnPrediction['churn_probability'];

        if ($classification === 'high' && $probability >= 70) {
            return [
                'type' => 'churn_based',
                'action' => 'Intervention urgente pour prévenir le churn',
                'priority' => 'critical',
                'justification' => "Probabilité de churn élevée ({$probability}%). Action immédiate requise.",
                'details' => [
                    'churn_probability' => $probability,
                    'classification' => $classification,
                    'factors' => $churnPrediction['factors'],
                ],
            ];
        } elseif ($classification === 'medium' && $probability >= 40) {
            return [
                'type' => 'churn_based',
                'action' => 'Relancer le créateur pour réduire le risque de churn',
                'priority' => 'high',
                'justification' => "Probabilité de churn modérée ({$probability}%). Intervention proactive recommandée.",
                'details' => [
                    'churn_probability' => $probability,
                    'classification' => $classification,
                ],
            ];
        }

        return null;
    }

    /**
     * Recommandations d'amélioration
     * 
     * @param CreatorProfile $creator
     * @param array $decisionScore
     * @return array
     */
    private function getImprovementRecommendations(CreatorProfile $creator, array $decisionScore): array
    {
        $recommendations = [];
        $components = $decisionScore['components'];

        // Amélioration financière
        if ($components['financial_health'] < 70) {
            $recommendations[] = [
                'type' => 'improvement',
                'action' => 'Améliorer la santé financière',
                'priority' => 'medium',
                'justification' => 'La santé financière peut être améliorée (actuellement ' . round($components['financial_health'], 0) . '/100).',
                'details' => [
                    'current_score' => $components['financial_health'],
                    'suggestions' => [
                        'Vérifier que l\'abonnement est actif et payé',
                        'Compléter l\'onboarding Stripe si nécessaire',
                    ],
                ],
            ];
        }

        // Amélioration opérationnelle
        if ($components['operational_health'] < 70) {
            $recommendations[] = [
                'type' => 'improvement',
                'action' => 'Améliorer les opérations',
                'priority' => 'medium',
                'justification' => 'Les opérations peuvent être améliorées (actuellement ' . round($components['operational_health'], 0) . '/100).',
                'details' => [
                    'current_score' => $components['operational_health'],
                    'suggestions' => [
                        'Compléter le profil créateur',
                        'Vérifier les documents',
                        'Finaliser l\'onboarding Stripe',
                    ],
                ],
            ];
        }

        // Amélioration engagement
        if ($components['engagement_level'] < 70) {
            $recommendations[] = [
                'type' => 'improvement',
                'action' => 'Augmenter l\'engagement',
                'priority' => 'low',
                'justification' => 'L\'engagement peut être amélioré (actuellement ' . round($components['engagement_level'], 0) . '/100).',
                'details' => [
                    'current_score' => $components['engagement_level'],
                    'suggestions' => [
                        'Ajouter plus de produits actifs',
                        'Créer des collections',
                        'Utiliser régulièrement le dashboard',
                    ],
                ],
            ];
        }

        return $recommendations;
    }
}


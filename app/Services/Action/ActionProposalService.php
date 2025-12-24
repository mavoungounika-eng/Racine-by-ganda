<?php

namespace App\Services\Action;

use App\Models\CreatorProfile;
use App\Services\Alerts\FinancialAlertService;
use App\Services\Decision\ChurnPredictionService;
use App\Services\Decision\CreatorDecisionScoreService;
use App\Services\Risk\CreatorRiskAssessmentService;

/**
 * Service de Proposition d'Actions
 * 
 * Phase 8.1 - Transformer scores/alertes/risques en actions proposées
 * 
 * RÈGLE D'OR : PROPOSE, N'EXÉCUTE JAMAIS
 */
class ActionProposalService
{
    protected CreatorDecisionScoreService $decisionScoreService;
    protected ChurnPredictionService $churnPredictionService;
    protected CreatorRiskAssessmentService $riskService;
    protected FinancialAlertService $alertService;

    public function __construct(
        CreatorDecisionScoreService $decisionScoreService,
        ChurnPredictionService $churnPredictionService,
        CreatorRiskAssessmentService $riskService,
        FinancialAlertService $alertService
    ) {
        $this->decisionScoreService = $decisionScoreService;
        $this->churnPredictionService = $churnPredictionService;
        $this->riskService = $riskService;
        $this->alertService = $alertService;
    }

    /**
     * Proposer des actions pour un créateur
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    public function proposeActions(CreatorProfile $creator): array
    {
        $proposals = [];

        // Analyser le créateur
        $decisionScore = $this->decisionScoreService->calculateDecisionScore($creator);
        $churnPrediction = $this->churnPredictionService->predictChurn($creator);
        $riskAssessment = $this->riskService->assessCreatorRisk($creator);
        $alerts = $this->alertService->checkCreatorAlerts($creator);

        // Proposition 1 : Basée sur le risque
        $riskProposal = $this->proposeActionFromRisk($creator, $riskAssessment);
        if ($riskProposal) {
            $proposals[] = $riskProposal;
        }

        // Proposition 2 : Basée sur les alertes
        $alertProposals = $this->proposeActionsFromAlerts($creator, $alerts);
        $proposals = array_merge($proposals, $alertProposals);

        // Proposition 3 : Basée sur le churn
        $churnProposal = $this->proposeActionFromChurn($creator, $churnPrediction);
        if ($churnProposal) {
            $proposals[] = $churnProposal;
        }

        // Proposition 4 : Basée sur le score décisionnel
        $scoreProposal = $this->proposeActionFromScore($creator, $decisionScore);
        if ($scoreProposal) {
            $proposals[] = $scoreProposal;
        }

        // Si aucune action critique, proposer MONITOR
        if (empty($proposals)) {
            $proposals[] = $this->createMonitorProposal($creator);
        }

        // Trier par priorité (confidence + risk_level)
        usort($proposals, function ($a, $b) {
            $priorityA = ($a['confidence'] ?? 0) + ($this->getRiskWeight($a['risk_level'] ?? 'low'));
            $priorityB = ($b['confidence'] ?? 0) + ($this->getRiskWeight($b['risk_level'] ?? 'low'));
            return $priorityB <=> $priorityA;
        });

        return [
            'proposals' => $proposals,
            'total_count' => count($proposals),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Proposer une action basée sur le risque
     * 
     * @param CreatorProfile $creator
     * @param array $riskAssessment
     * @return array|null
     */
    private function proposeActionFromRisk(CreatorProfile $creator, array $riskAssessment): ?array
    {
        $riskLevel = $riskAssessment['risk_level'];
        $riskScore = $riskAssessment['risk_score'];
        $recommendedAction = $riskAssessment['recommended_action'];

        if ($riskLevel === 'high' && $recommendedAction === 'suspend') {
            return [
                'action' => 'PROPOSE_SUSPENSION',
                'target_type' => 'creator',
                'target_id' => $creator->id,
                'confidence' => min(100, $riskScore),
                'justification' => 'Risque élevé détecté : ' . implode(', ', $riskAssessment['reasons']),
                'risk_level' => 'high',
                'source' => ['risk_engine'],
                'source_data' => $riskAssessment,
            ];
        } elseif ($riskLevel === 'medium') {
            return [
                'action' => 'FLAG_FOR_REVIEW',
                'target_type' => 'creator',
                'target_id' => $creator->id,
                'confidence' => 70.0,
                'justification' => 'Risque modéré nécessitant une révision : ' . implode(', ', $riskAssessment['reasons']),
                'risk_level' => 'medium',
                'source' => ['risk_engine'],
                'source_data' => $riskAssessment,
            ];
        }

        return null;
    }

    /**
     * Proposer des actions basées sur les alertes
     * 
     * @param CreatorProfile $creator
     * @param array $alerts
     * @return array
     */
    private function proposeActionsFromAlerts(CreatorProfile $creator, array $alerts): array
    {
        $proposals = [];

        foreach ($alerts as $alert) {
            $severity = $alert['severity'] ?? 'medium';
            $actionType = $this->mapAlertToAction($alert['type'] ?? '');
            
            if ($actionType === 'NO_ACTION') {
                continue;
            }

            $proposals[] = [
                'action' => $actionType,
                'target_type' => 'creator',
                'target_id' => $creator->id,
                'confidence' => $this->getConfidenceFromSeverity($severity),
                'justification' => $alert['message'] ?? 'Alerte détectée',
                'risk_level' => $severity,
                'source' => ['alert_system'],
                'source_data' => $alert,
            ];
        }

        return $proposals;
    }

    /**
     * Proposer une action basée sur le churn
     * 
     * @param CreatorProfile $creator
     * @param array $churnPrediction
     * @return array|null
     */
    private function proposeActionFromChurn(CreatorProfile $creator, array $churnPrediction): ?array
    {
        $classification = $churnPrediction['classification'];
        $probability = $churnPrediction['churn_probability'];

        if ($classification === 'high' && $probability >= 70) {
            return [
                'action' => 'SEND_REMINDER',
                'target_type' => 'creator',
                'target_id' => $creator->id,
                'confidence' => $probability,
                'justification' => "Probabilité de churn élevée ({$probability}%). Intervention urgente recommandée.",
                'risk_level' => 'high',
                'source' => ['churn_prediction'],
                'source_data' => $churnPrediction,
            ];
        } elseif ($classification === 'medium' && $probability >= 40) {
            return [
                'action' => 'SEND_REMINDER',
                'target_type' => 'creator',
                'target_id' => $creator->id,
                'confidence' => $probability * 0.8,
                'justification' => "Probabilité de churn modérée ({$probability}%). Relance proactive recommandée.",
                'risk_level' => 'medium',
                'source' => ['churn_prediction'],
                'source_data' => $churnPrediction,
            ];
        }

        return null;
    }

    /**
     * Proposer une action basée sur le score décisionnel
     * 
     * @param CreatorProfile $creator
     * @param array $decisionScore
     * @return array|null
     */
    private function proposeActionFromScore(CreatorProfile $creator, array $decisionScore): ?array
    {
        $globalScore = $decisionScore['global_score'];
        $grade = $decisionScore['qualitative_grade'];

        if ($globalScore < 40) {
            return [
                'action' => 'FLAG_FOR_REVIEW',
                'target_type' => 'creator',
                'target_id' => $creator->id,
                'confidence' => 80.0,
                'justification' => "Score décisionnel faible ({$grade}, {$globalScore}/100). Révision nécessaire.",
                'risk_level' => 'medium',
                'source' => ['decision_score'],
                'source_data' => $decisionScore,
            ];
        }

        return null;
    }

    /**
     * Créer une proposition MONITOR par défaut
     * 
     * @param CreatorProfile $creator
     * @return array
     */
    private function createMonitorProposal(CreatorProfile $creator): array
    {
        return [
            'action' => 'MONITOR',
            'target_type' => 'creator',
            'target_id' => $creator->id,
            'confidence' => 50.0,
            'justification' => 'Surveillance continue recommandée',
            'risk_level' => 'low',
            'source' => ['default'],
            'source_data' => [],
        ];
    }

    /**
     * Mapper un type d'alerte à un type d'action
     * 
     * @param string $alertType
     * @return string
     */
    private function mapAlertToAction(string $alertType): string
    {
        $mapping = [
            'subscription_unpaid' => 'PROPOSE_SUSPENSION',
            'subscription_past_due' => 'SEND_REMINDER',
            'stripe_charges_disabled' => 'REQUEST_KYC_UPDATE',
            'stripe_payouts_disabled' => 'REQUEST_KYC_UPDATE',
            'onboarding_incomplete' => 'REQUEST_KYC_UPDATE',
            'not_eligible_payments' => 'FLAG_FOR_REVIEW',
        ];

        return $mapping[$alertType] ?? 'NO_ACTION';
    }

    /**
     * Obtenir la confiance depuis la sévérité
     * 
     * @param string $severity
     * @return float
     */
    private function getConfidenceFromSeverity(string $severity): float
    {
        return match ($severity) {
            'high' => 90.0,
            'medium' => 70.0,
            'low' => 50.0,
            default => 50.0,
        };
    }

    /**
     * Obtenir le poids d'un niveau de risque
     * 
     * @param string $riskLevel
     * @return int
     */
    private function getRiskWeight(string $riskLevel): int
    {
        return match ($riskLevel) {
            'high' => 50,
            'medium' => 25,
            'low' => 0,
            default => 0,
        };
    }
}




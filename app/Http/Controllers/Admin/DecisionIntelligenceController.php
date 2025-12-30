<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Decision\CreatorDecisionSnapshotDTO;
use App\Models\CreatorProfile;
use App\Services\Alerts\FinancialAlertService;
use App\Services\Decision\ChurnPredictionService;
use App\Services\Decision\CreatorDecisionScoreService;
use App\Services\Decision\RecommendationEngineService;
use App\Services\Risk\CreatorRiskAssessmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur Admin - Intelligence Décisionnelle
 * 
 * Phase 7.5 - Interface admin (lecture seule)
 */
class DecisionIntelligenceController
{
    protected CreatorDecisionScoreService $decisionScoreService;
    protected ChurnPredictionService $churnPredictionService;
    protected RecommendationEngineService $recommendationEngine;
    protected CreatorRiskAssessmentService $riskService;
    protected FinancialAlertService $alertService;

    public function __construct(
        CreatorDecisionScoreService $decisionScoreService,
        ChurnPredictionService $churnPredictionService,
        RecommendationEngineService $recommendationEngine,
        CreatorRiskAssessmentService $riskService,
        FinancialAlertService $alertService
    ) {
        $this->decisionScoreService = $decisionScoreService;
        $this->churnPredictionService = $churnPredictionService;
        $this->recommendationEngine = $recommendationEngine;
        $this->riskService = $riskService;
        $this->alertService = $alertService;
    }

    /**
     * Obtenir l'analyse décisionnelle complète d'un créateur
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function creator(Request $request, int $id): JsonResponse
    {
        $creator = CreatorProfile::findOrFail($id);

        // Calculer tous les indicateurs
        $decisionScore = $this->decisionScoreService->calculateDecisionScore($creator);
        $churnPrediction = $this->churnPredictionService->predictChurn($creator);
        $recommendations = $this->recommendationEngine->generateRecommendations($creator);
        $riskAssessment = $this->riskService->assessCreatorRisk($creator);
        $alerts = $this->alertService->checkCreatorAlerts($creator);

        // Créer le snapshot
        $snapshot = new CreatorDecisionSnapshotDTO(
            creatorId: $creator->id,
            creatorName: $creator->brand_name ?? 'N/A',
            decisionScore: $decisionScore,
            churnPrediction: $churnPrediction,
            recommendations: $recommendations,
            riskAssessment: $riskAssessment,
            alerts: $alerts,
            snapshotDate: now()->toIso8601String(),
            metadata: [
                'creator_status' => $creator->status,
                'creator_is_active' => $creator->is_active,
                'creator_is_verified' => $creator->is_verified,
            ]
        );

        return response()->json($snapshot->toArray());
    }

    /**
     * Obtenir une vue d'ensemble des décisions
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function overview(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $minScore = $request->input('min_score', 0);
        $maxScore = $request->input('max_score', 100);

        // Récupérer les créateurs avec abonnements actifs
        $creators = CreatorProfile::whereHas('subscriptions', function ($query) {
            $query->whereIn('status', ['active', 'trialing']);
        })
        ->limit($limit)
        ->get();

        $overview = [];

        foreach ($creators as $creator) {
            $decisionScore = $this->decisionScoreService->calculateDecisionScore($creator);
            $globalScore = $decisionScore['global_score'];

            // Filtrer par score si demandé
            if ($globalScore < $minScore || $globalScore > $maxScore) {
                continue;
            }

            $churnPrediction = $this->churnPredictionService->predictChurn($creator);
            $riskAssessment = $this->riskService->assessCreatorRisk($creator);

            $overview[] = [
                'creator_id' => $creator->id,
                'creator_name' => $creator->brand_name ?? 'N/A',
                'decision_score' => $globalScore,
                'qualitative_grade' => $decisionScore['qualitative_grade'],
                'churn_probability' => $churnPrediction['churn_probability'],
                'churn_classification' => $churnPrediction['classification'],
                'risk_level' => $riskAssessment['risk_level'],
                'risk_score' => $riskAssessment['risk_score'],
            ];
        }

        // Trier par score décisionnel décroissant
        usort($overview, function ($a, $b) {
            return $b['decision_score'] <=> $a['decision_score'];
        });

        return response()->json([
            'overview' => $overview,
            'total_creators' => count($overview),
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}




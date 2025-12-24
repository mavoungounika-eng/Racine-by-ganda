<?php

namespace App\Http\Controllers\Admin;

use App\DTO\BI\FinancialSnapshotDTO;
use App\Services\Alerts\FinancialAlertService;
use App\Services\BI\AdvancedKpiService;
use App\Services\BI\AdminFinancialDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur Admin - Dashboard Financier
 * 
 * Phase 6.1 - Endpoint API pour le dashboard admin
 */
class FinancialDashboardController
{
    protected AdminFinancialDashboardService $dashboardService;
    protected AdvancedKpiService $kpiService;
    protected FinancialAlertService $alertService;

    public function __construct(
        AdminFinancialDashboardService $dashboardService,
        AdvancedKpiService $kpiService,
        FinancialAlertService $alertService
    ) {
        $this->dashboardService = $dashboardService;
        $this->kpiService = $kpiService;
        $this->alertService = $alertService;
    }

    /**
     * Obtenir toutes les métriques du dashboard financier
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Récupérer les métriques de base
        $revenueMetrics = $this->dashboardService->getRevenueMetrics();
        $subscriptionMetrics = $this->dashboardService->getSubscriptionMetrics();
        $creatorMetrics = $this->dashboardService->getCreatorMetrics();
        $stripeHealthMetrics = $this->dashboardService->getStripeHealthMetrics();
        $riskMetrics = $this->dashboardService->getRiskMetrics();

        // Récupérer les KPI avancés
        $advancedKpis = [
            'churn_rate_month' => $this->kpiService->calculateChurnRate('month'),
            'churn_rate_year' => $this->kpiService->calculateChurnRate('year'),
            'ltv' => $this->kpiService->calculateLtv(),
            'arpu' => $this->kpiService->calculateArpu(),
            'average_subscription_duration' => $this->kpiService->calculateAverageSubscriptionDuration(),
        ];

        // Récupérer les alertes
        $globalAlerts = $this->alertService->checkGlobalAlerts();

        // Construire la réponse
        $response = [
            'timestamp' => now()->toIso8601String(),
            'revenue' => $revenueMetrics,
            'subscriptions' => $subscriptionMetrics,
            'creators' => $creatorMetrics,
            'stripe_health' => $stripeHealthMetrics,
            'risks' => $riskMetrics,
            'advanced_kpis' => $advancedKpis,
            'alerts' => $globalAlerts,
        ];

        return response()->json($response);
    }

    /**
     * Obtenir un snapshot financier complet (pour export BI)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function snapshot(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');

        // Récupérer toutes les métriques
        $revenueMetrics = $this->dashboardService->getRevenueMetrics();
        $subscriptionMetrics = $this->dashboardService->getSubscriptionMetrics();
        $creatorMetrics = $this->dashboardService->getCreatorMetrics();
        $stripeHealthMetrics = $this->dashboardService->getStripeHealthMetrics();
        $riskMetrics = $this->dashboardService->getRiskMetrics();

        $advancedKpis = [
            'churn_rate' => $this->kpiService->calculateChurnRate($period),
            'ltv' => $this->kpiService->calculateLtv(),
            'arpu' => $this->kpiService->calculateArpu(),
            'average_subscription_duration' => $this->kpiService->calculateAverageSubscriptionDuration(),
        ];

        $alerts = $this->alertService->checkGlobalAlerts();

        // Créer le DTO
        $snapshot = new FinancialSnapshotDTO(
            revenueMetrics: $revenueMetrics,
            subscriptionMetrics: $subscriptionMetrics,
            creatorMetrics: $creatorMetrics,
            stripeHealthMetrics: $stripeHealthMetrics,
            riskMetrics: $riskMetrics,
            advancedKpis: $advancedKpis,
            alerts: $alerts,
            snapshotDate: now()->toIso8601String(),
            period: $period
        );

        return response()->json($snapshot->toArray());
    }
}

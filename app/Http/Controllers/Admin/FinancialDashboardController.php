<?php

namespace App\Http\Controllers\Admin;

use App\DTO\BI\FinancialSnapshotDTO;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use App\Services\Alerts\FinancialAlertService;
use App\Services\BI\AdvancedKpiService;
use App\Services\BI\AdminFinancialDashboardService;
use Carbon\Carbon;
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
     * Afficher le dashboard financier (HTML ou JSON selon Accept)
     */
    public function showDashboard(Request $request)
    {
        if ($request->wantsJson()) {
            return $this->index($request);
        }

        $month = $request->input('month', now()->format('Y-m'));

        $revenueMetrics      = $this->dashboardService->getRevenueMetrics();
        $subscriptionMetrics = $this->dashboardService->getSubscriptionMetrics();
        $creatorMetrics      = $this->dashboardService->getCreatorMetrics();
        $stripeHealthMetrics = $this->dashboardService->getStripeHealthMetrics();
        $riskMetrics         = $this->dashboardService->getRiskMetrics();

        // Payment stats for the selected month
        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
        $monthEnd   = $monthStart->copy()->endOfMonth();

        $successfulPayments = Payment::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 'paid')->count();
        $failedPayments = Payment::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('status', 'failed')->count();
        $totalPayments  = $successfulPayments + $failedPayments;
        $failureRate    = $totalPayments > 0 ? round(($failedPayments / $totalPayments) * 100, 2) : 0;

        // Stripe health rates
        $chargesRate    = $stripeHealthMetrics['charges_enabled_percent'] ?? 0;
        $payoutsRate    = $stripeHealthMetrics['payouts_enabled_percent'] ?? 0;
        $onboardingRate = $stripeHealthMetrics['onboarding_complete_percent'] ?? 0;
        $healthScore    = round(($chargesRate + $payoutsRate + $onboardingRate) / 3, 2);

        // Creator blocked breakdown (service returns single int; approximate split)
        $blockedTotal       = $creatorMetrics['blocked'] ?? 0;
        $blockedStripe      = min($blockedTotal, $stripeHealthMetrics['failed_accounts'] ?? 0);
        $blockedSubscription = max(0, $blockedTotal - $blockedStripe);

        // Risk totals
        $pastDue     = $riskMetrics['creators_past_due'] ?? 0;
        $unpaid      = $riskMetrics['creators_unpaid'] ?? 0;
        $highRisk    = $riskMetrics['high_risk_creators'] ?? 0;
        $totalAtRisk = $highRisk + $unpaid;

        $dashboardMetrics = [
            'revenue' => [
                'mrr'         => $revenueMetrics['mrr'] ?? 0,
                'arr'         => $revenueMetrics['arr'] ?? 0,
                'net_revenue' => $revenueMetrics['current_month_revenue'] ?? 0,
            ],
            'subscriptions' => [
                'active'              => $subscriptionMetrics['active'] ?? 0,
                'canceled_this_month' => 0,
            ],
            'creators' => [
                'active'      => $creatorMetrics['active'] ?? 0,
                'blocked'     => [
                    'total'        => $blockedTotal,
                    'stripe'       => $blockedStripe,
                    'subscription' => $blockedSubscription,
                ],
                'in_onboarding' => $creatorMetrics['onboarding_incomplete'] ?? 0,
                'at_risk'       => $pastDue,
            ],
            'payments' => [
                'successful'   => $successfulPayments,
                'failed'       => $failedPayments,
                'failure_rate' => $failureRate,
            ],
            'webhooks' => [
                'recent' => StripeWebhookEvent::latest()->limit(10)->get(),
            ],
            'stripe_incidents' => [],
        ];

        $strategicMetrics = [
            'churn_rate'        => $this->kpiService->calculateChurnRate('month'),
            'arpu'              => $this->kpiService->calculateArpu(),
            'ltv'               => $this->kpiService->calculateLtv(),
            'activation_rate'   => $onboardingRate,
            'stripe_health_score' => [
                'score'                  => $healthScore,
                'charges_enabled_rate'   => $chargesRate,
                'payouts_enabled_rate'   => $payoutsRate,
                'onboarding_complete_rate' => $onboardingRate,
            ],
        ];

        $riskStatistics = [
            'total_at_risk' => $totalAtRisk,
            'by_level'      => [
                'critical' => $unpaid,
                'high'     => $highRisk,
                'medium'   => $pastDue,
            ],
        ];

        return view('admin.financial.dashboard', compact(
            'month', 'dashboardMetrics', 'strategicMetrics', 'riskStatistics'
        ));
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

<?php

namespace Modules\Analytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Analytics\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsDashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Dashboard principal Analytics
     */
    public function index()
    {
        $kpis = $this->analyticsService->getMainKPIs();
        $insights = $this->analyticsService->getSmartInsights();
        $topProducts = $this->analyticsService->getTopProducts(5);

        return view('analytics::dashboard', compact('kpis', 'insights', 'topProducts'));
    }

    /**
     * API: Données du graphique revenus
     */
    public function revenueChart(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        return response()->json($this->analyticsService->getRevenueChart($days));
    }

    /**
     * API: Données du graphique statuts commandes
     */
    public function ordersStatusChart(): JsonResponse
    {
        return response()->json($this->analyticsService->getOrdersStatusChart());
    }

    /**
     * API: Revenus par catégorie
     */
    public function categoryChart(): JsonResponse
    {
        return response()->json($this->analyticsService->getRevenueByCategory());
    }

    /**
     * API: Comparatif mensuel
     */
    public function monthlyChart(): JsonResponse
    {
        return response()->json($this->analyticsService->getMonthlyComparison());
    }

    /**
     * API: Heures de pointe
     */
    public function peakHoursChart(): JsonResponse
    {
        return response()->json($this->analyticsService->getPeakHours());
    }

    /**
     * API: KPIs temps réel
     */
    public function realTimeKpis(): JsonResponse
    {
        return response()->json($this->analyticsService->getMainKPIs());
    }

    /**
     * API: Insights intelligents
     */
    public function insights(): JsonResponse
    {
        return response()->json($this->analyticsService->getSmartInsights());
    }
}


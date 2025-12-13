<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Contrôleur pour le module Analytics / Dashboard
 * 
 * Phase 4 : Module Analytics / Dashboard
 * 
 * Routes :
 * - GET /admin/analytics → Vue d'ensemble
 * - GET /admin/analytics/funnel → Dashboard funnel (conversion)
 * - GET /admin/analytics/sales → Dashboard ventes & CA
 */
class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Page d'accueil Analytics (vue d'ensemble)
     */
    public function index()
    {
        // Par défaut : 7 derniers jours
        $endDate = now();
        $startDate = now()->subDays(7);

        // KPIs rapides
        $funnelStats = $this->analyticsService->getFunnelStats($startDate, $endDate);
        $salesStats = $this->analyticsService->getSalesStats($startDate, $endDate);

        return view('admin.analytics.index', compact('funnelStats', 'salesStats'));
    }

    /**
     * Dashboard Funnel (conversion)
     */
    public function funnel(Request $request)
    {
        // Gestion de la période
        $period = $request->get('period', '7days');
        [$startDate, $endDate] = $this->parsePeriod($period, $request);

        // Filtre par méthode de paiement
        $paymentMethod = $request->get('payment_method');

        // Force refresh si demandé
        $forceRefresh = $request->has('refresh');

        // Récupérer les statistiques
        $stats = $this->analyticsService->getFunnelStats($startDate, $endDate, $paymentMethod, $forceRefresh);

        return view('admin.analytics.funnel', compact('stats', 'period', 'paymentMethod'));
    }

    /**
     * Dashboard Ventes & Chiffres d'affaires
     */
    public function sales(Request $request)
    {
        // Gestion de la période
        $period = $request->get('period', '7days');
        [$startDate, $endDate] = $this->parsePeriod($period, $request);

        // Force refresh si demandé
        $forceRefresh = $request->has('refresh');

        // Récupérer les statistiques
        $stats = $this->analyticsService->getSalesStats($startDate, $endDate, $forceRefresh);

        return view('admin.analytics.sales', compact('stats', 'period'));
    }

    /**
     * Parser la période depuis la requête
     * 
     * @param string $period
     * @param Request $request
     * @return array [Carbon $startDate, Carbon $endDate]
     */
    protected function parsePeriod(string $period, Request $request): array
    {
        $endDate = now()->endOfDay();

        switch ($period) {
            case '7days':
                $startDate = now()->subDays(7)->startOfDay();
                break;
            case '30days':
                $startDate = now()->subDays(30)->startOfDay();
                break;
            case 'this_month':
                $startDate = now()->startOfMonth()->startOfDay();
                break;
            case 'custom':
                $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
                break;
            default:
                $startDate = now()->subDays(7)->startOfDay();
        }

        return [$startDate, $endDate];
    }
}


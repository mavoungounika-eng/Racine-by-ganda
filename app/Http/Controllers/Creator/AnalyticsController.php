<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur Analytics pour les créateurs
 * 
 * Phase 4 : Structure préparée pour les statistiques créateur
 * 
 * TODO : Implémenter les méthodes pour afficher :
 * - CA du créateur
 * - Nombre de commandes contenant ses produits
 * - Top de ses produits
 * - Évolution dans le temps
 */
class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Dashboard Analytics créateur
     */
    public function index(Request $request)
    {
        $creatorId = Auth::id();
        
        // Par défaut : 30 derniers jours
        $endDate = now();
        $startDate = now()->subDays(30);

        // Gestion de la période
        $period = $request->get('period', '30days');
        [$startDate, $endDate] = $this->parsePeriod($period, $request);

        // Force refresh si demandé
        $forceRefresh = $request->has('refresh');

        // Récupérer les statistiques du créateur
        $stats = $this->analyticsService->getCreatorStats($creatorId, $startDate, $endDate, $forceRefresh);

        return view('creator.analytics.index', compact('stats', 'period'));
    }

    /**
     * Statistiques de ventes du créateur
     */
    public function sales(Request $request)
    {
        $creatorId = Auth::id();
        
        // Gestion de la période
        $period = $request->get('period', '30days');
        [$startDate, $endDate] = $this->parsePeriod($period, $request);

        // Force refresh si demandé
        $forceRefresh = $request->has('refresh');

        // Récupérer les statistiques de ventes
        $stats = $this->analyticsService->getCreatorStats($creatorId, $startDate, $endDate, $forceRefresh);

        return view('creator.analytics.sales', compact('stats', 'period'));
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
                $startDate = now()->subDays(30)->startOfDay();
        }

        return [$startDate, $endDate];
    }
}


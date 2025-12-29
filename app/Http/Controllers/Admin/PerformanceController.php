<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PerformanceMetric;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Contrôleur du dashboard de performance (Admin-only)
 * 
 * Visualise les métriques collectées par RecordPerformanceMetrics middleware.
 * Accessible uniquement aux administrateurs.
 * 
 * @package App\Http\Controllers\Admin
 */
class PerformanceController extends Controller
{
    /**
     * Dashboard global de performance
     * 
     * Affiche les moyennes 24h/7j et le top 5 des routes lentes
     * 
     * @return View
     */
    public function index(): View
    {
        // Moyennes sur 24h
        $stats24h = PerformanceMetric::where('created_at', '>=', now()->subDay())
            ->select(
                DB::raw('AVG(query_count) as avg_queries'),
                DB::raw('AVG(db_time_ms) as avg_db_time'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as total_requests')
            )
            ->first();

        // Moyennes sur 7 jours
        $stats7d = PerformanceMetric::where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('AVG(query_count) as avg_queries'),
                DB::raw('AVG(db_time_ms) as avg_db_time'),
                DB::raw('AVG(response_time_ms) as avg_response_time'),
                DB::raw('COUNT(*) as total_requests')
            )
            ->first();

        // Top 5 routes les plus lentes (par temps de réponse moyen)
        $slowestRoutes = PerformanceMetric::select('route')
            ->selectRaw('AVG(response_time_ms) as avg_response_time')
            ->selectRaw('AVG(query_count) as avg_queries')
            ->selectRaw('COUNT(*) as hits')
            ->groupBy('route')
            ->orderByDesc('avg_response_time')
            ->limit(5)
            ->get();

        return view('admin.performance.index', compact('stats24h', 'stats7d', 'slowestRoutes'));
    }

    /**
     * Analyse par route
     * 
     * Liste toutes les routes avec leurs statistiques
     * Permet le tri par différents critères
     * 
     * @param Request $request
     * @return View
     */
    public function routes(Request $request): View
    {
        $sortBy = $request->get('sort', 'avg_response_time');
        $sortDir = $request->get('dir', 'desc');

        // Valider les colonnes de tri
        $allowedSorts = ['avg_response_time', 'avg_queries', 'hits', 'route'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'avg_response_time';
        }

        // Statistiques par route
        $routeStats = PerformanceMetric::select('route')
            ->selectRaw('COUNT(*) as hits')
            ->selectRaw('AVG(query_count) as avg_queries')
            ->selectRaw('AVG(db_time_ms) as avg_db_time')
            ->selectRaw('AVG(response_time_ms) as avg_response_time')
            ->groupBy('route')
            ->orderBy($sortBy, $sortDir)
            ->paginate(20)
            ->withQueryString();

        return view('admin.performance.routes', compact('routeStats', 'sortBy', 'sortDir'));
    }

    /**
     * Alertes de performance
     * 
     * Affiche les routes dépassant les seuils critiques :
     * - 🔴 Critique : >30 queries ou >500ms
     * - 🟠 Alerte : >20 queries
     * 
     * @return View
     */
    public function alerts(): View
    {
        // Routes critiques (>30 queries OU >500ms)
        $criticalRoutes = PerformanceMetric::select('route')
            ->selectRaw('AVG(query_count) as avg_queries')
            ->selectRaw('AVG(response_time_ms) as avg_response_time')
            ->selectRaw('COUNT(*) as hits')
            ->groupBy('route')
            ->havingRaw('AVG(query_count) > 30 OR AVG(response_time_ms) > 500')
            ->orderByDesc('avg_queries')
            ->get();

        // Routes en alerte (>20 queries mais <30)
        $warningRoutes = PerformanceMetric::select('route')
            ->selectRaw('AVG(query_count) as avg_queries')
            ->selectRaw('AVG(response_time_ms) as avg_response_time')
            ->selectRaw('COUNT(*) as hits')
            ->groupBy('route')
            ->havingRaw('AVG(query_count) > 20 AND AVG(query_count) <= 30')
            ->havingRaw('AVG(response_time_ms) <= 500')
            ->orderByDesc('avg_queries')
            ->get();

        return view('admin.performance.alerts', compact('criticalRoutes', 'warningRoutes'));
    }
}

<?php

namespace Modules\Analytics\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Analytics\Services\AnalyticsService;
use Illuminate\Support\Carbon;

class AnalyticsExportController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Exporter le rapport en HTML (imprimable en PDF via navigateur)
     */
    public function exportReport(Request $request)
    {
        $kpis = $this->analyticsService->getMainKPIs();
        $topProducts = $this->analyticsService->getTopProducts(10);
        $insights = $this->analyticsService->getSmartInsights();
        $monthlyData = $this->analyticsService->getMonthlyComparison();
        
        $reportDate = Carbon::now()->format('d/m/Y H:i');
        $periodStart = Carbon::now()->startOfMonth()->format('d/m/Y');
        $periodEnd = Carbon::now()->format('d/m/Y');

        return view('analytics::export.report', compact(
            'kpis',
            'topProducts',
            'insights',
            'monthlyData',
            'reportDate',
            'periodStart',
            'periodEnd'
        ));
    }

    /**
     * Exporter les données en JSON (pour intégration)
     */
    public function exportJson()
    {
        $data = [
            'generated_at' => Carbon::now()->toIso8601String(),
            'kpis' => $this->analyticsService->getMainKPIs(),
            'top_products' => $this->analyticsService->getTopProducts(10),
            'insights' => $this->analyticsService->getSmartInsights(),
            'monthly_comparison' => $this->analyticsService->getMonthlyComparison(),
            'peak_hours' => $this->analyticsService->getPeakHours(),
        ];

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="analytics-report-' . date('Y-m-d') . '.json"');
    }

    /**
     * Exporter les données en CSV
     */
    public function exportCsv()
    {
        $topProducts = $this->analyticsService->getTopProducts(50);
        
        $csv = "Rang,Produit,Quantité Vendue,Revenu (FCFA)\n";
        
        foreach ($topProducts as $index => $product) {
            $csv .= sprintf(
                "%d,\"%s\",%d,%s\n",
                $index + 1,
                str_replace('"', '""', $product['title']),
                $product['total_sold'],
                $product['revenue']
            );
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="top-produits-' . date('Y-m-d') . '.csv"');
    }
}


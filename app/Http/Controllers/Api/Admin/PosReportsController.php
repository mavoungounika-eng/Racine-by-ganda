<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Pos\PosReportsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PosReportsController - API Reports POS
 *
 * Endpoints pour récupérer analytics POS:
 * - Daily reports
 * - Period reports
 * - Discrepancy analysis
 * - Operator performance
 * - CSV exports
 *
 * Autorisation: Admin seulement
 */
class PosReportsController extends Controller
{
    public function __construct(
        protected PosReportsService $reportsService
    ) {
        // Vérifier authorization admin
        $this->middleware('auth:sanctum');
        $this->middleware('role:admin');
    }

    /**
     * Rapport journalier
     *
     * GET /api/admin/pos/reports/daily?date=2026-01-28
     */
    public function daily(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        try {
            $report = $this->reportsService->getDailyReport(
                new \DateTime($validated['date'])
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rapport période
     *
     * GET /api/admin/pos/reports/period?start=2026-01-20&end=2026-01-28
     */
    public function period(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
        ]);

        try {
            $report = $this->reportsService->getPeriodReport(
                new \DateTime($validated['start']),
                new \DateTime($validated['end'])
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Rapport discrepancies
     *
     * GET /api/admin/pos/reports/discrepancies?start=2026-01-01&end=2026-01-28&threshold=1.00
     */
    public function discrepancies(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            'threshold' => 'numeric|min:0|default:1.00',
        ]);

        try {
            $report = $this->reportsService->getDiscrepancyReport(
                new \DateTime($validated['start']),
                new \DateTime($validated['end']),
                $validated['threshold']
            );

            return response()->json([
                'success' => true,
                'data' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Export CSV
     *
     * GET /api/admin/pos/reports/export?start=2026-01-01&end=2026-01-28&format=csv
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
            'format' => 'in:csv,json|default:csv',
        ]);

        try {
            $data = $this->reportsService->exportSessionsData(
                new \DateTime($validated['start']),
                new \DateTime($validated['end'])
            );

            if ($validated['format'] === 'csv') {
                return $this->exportCsv($data);
            }

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Helper: Export CSV
     */
    private function exportCsv(array $data)
    {
        if (empty($data)) {
            return response()->json(['error' => 'No data'], 404);
        }

        // Headers CSV
        $headers = array_keys($data[0]);

        // Créer CSV en mémoire
        $csv = fopen('php://memory', 'r+');

        // Écrire headers
        fputcsv($csv, $headers);

        // Écrire rows
        foreach ($data as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        // Retourner comme fichier
        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pos_reports_' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Dashboard KPIs summary
     *
     * GET /api/admin/pos/reports/dashboard?days=30
     */
    public function dashboard(Request $request): JsonResponse
    {
        $days = $request->validate(['days' => 'integer|min:1|max:365|default:30'])['days'];

        $startDate = now()->subDays($days);
        $endDate = now();

        try {
            $periodReport = $this->reportsService->getPeriodReport($startDate, $endDate);
            $discrepancies = $this->reportsService->getDiscrepancyReport($startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => "{$days} derniers jours",
                    'summary' => [
                        'total_sessions' => $periodReport['total_sessions'],
                        'total_sales' => $periodReport['total_sales'],
                        'average_session_sales' => round($periodReport['average_session_sales'], 2),
                        'discrepancy_rate' => round($periodReport['discrepancy_rate'], 2),
                        'total_cash_discrepancies' => round($periodReport['total_cash_discrepancies'], 2),
                    ],
                    'top_performers' => $this->getTopPerformers($periodReport),
                    'alerts' => [
                        'high_discrepancy_count' => $discrepancies['discrepancies_count'],
                        'max_discrepancy' => round($discrepancies['max_discrepancy'] ?? 0, 2),
                        'total_discrepancy_amount' => round($discrepancies['total_discrepancy_amount'] ?? 0, 2),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Helper: Top performers
     */
    private function getTopPerformers(array $periodReport): array
    {
        if (empty($periodReport['daily_data'])) {
            return [];
        }

        // Aggréger performances
        $performers = [];

        foreach ($periodReport['daily_data'] as $day) {
            if (!isset($day['performance'])) continue;

            foreach ($day['performance'] as $name => $stats) {
                if (!isset($performers[$name])) {
                    $performers[$name] = [
                        'sessions' => 0,
                        'sales' => 0,
                        'discrepancies' => 0,
                    ];
                }

                $performers[$name]['sessions'] += $stats['sessions'];
                $performers[$name]['sales'] += $stats['total_sales'];
                $performers[$name]['discrepancies'] += $stats['discrepancies'];
            }
        }

        // Trier par total ventes
        usort($performers, fn($a, $b) => $b['sales'] <=> $a['sales']);

        return array_slice($performers, 0, 5);
    }
}

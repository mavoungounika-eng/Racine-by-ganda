<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Pos\PosReportsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use DateTime;

/**
 * POS Analytics Controller
 * 
 * Fournit des rapports et analytics pour le système POS:
 * - Rapports journaliers/période
 * - Performance opérateurs
 * - Analyse discrepancies
 * - Export CSV/PDF
 */
class PosAnalyticsController extends Controller
{
    private PosReportsService $reportsService;

    public function __construct(PosReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    /**
     * Afficher la page analytics POS
     */
    public function index(): View
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        // Rapport du jour par défaut
        $today = new DateTime();
        $dailyReport = $this->reportsService->getDailyReport($today);

        return view('admin.pos.analytics', [
            'dailyReport' => $dailyReport,
            'selectedDate' => $today->format('Y-m-d'),
        ]);
    }

    /**
     * Obtenir rapport journalier (AJAX)
     */
    public function getDailyReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $request->validate([
            'date' => 'required|date',
        ]);

        $date = new DateTime($request->date);
        $report = $this->reportsService->getDailyReport($date);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Obtenir rapport période (AJAX)
     */
    public function getPeriodReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = new DateTime($request->start_date);
        $endDate = new DateTime($request->end_date);

        $report = $this->reportsService->getPeriodReport($startDate, $endDate);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Obtenir rapport discrepancies (AJAX)
     */
    public function getDiscrepancyReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'threshold' => 'nullable|numeric|min:0',
        ]);

        $startDate = new DateTime($request->start_date);
        $endDate = new DateTime($request->end_date);
        $threshold = $request->threshold ?? 1.00;

        $report = $this->reportsService->getDiscrepancyReport($startDate, $endDate, $threshold);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }

    /**
     * Export CSV des sessions
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = new DateTime($request->start_date);
        $endDate = new DateTime($request->end_date);

        $data = $this->reportsService->exportSessionsData($startDate, $endDate);

        $filename = 'pos_sessions_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, [
                'Session ID',
                'Date',
                'Opérateur',
                'Opening Cash',
                'Expected Cash',
                'Actual Cash',
                'Cash Difference',
                'Total Sales',
                'Transactions Count',
                'Status',
                'Opened At',
                'Closed At',
            ]);

            // Rows
            foreach ($data as $row) {
                fputcsv($file, [
                    $row['session_id'],
                    $row['date'],
                    $row['operator_name'],
                    number_format($row['opening_cash'], 2),
                    number_format($row['expected_cash'], 2),
                    number_format($row['actual_cash'], 2),
                    number_format($row['cash_difference'], 2),
                    number_format($row['total_sales'], 2),
                    $row['transactions_count'],
                    $row['status'],
                    $row['opened_at'],
                    $row['closed_at'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

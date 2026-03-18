<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Services\Pos\PosReportsService;
use App\Http\Responses\PosApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Exports\PosReportExport;
use Maatwebsite\Excel\Facades\Excel;

class PosAnalyticsController extends Controller
{
    private PosReportsService $reportsService;

    public function __construct(PosReportsService $reportsService)
    {
        $this->reportsService = $reportsService;
    }

    public function daily(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'machine_id' => 'required|string',
        ]);

        $date = Carbon::parse($request->date);
        $machineId = $request->machine_id;

        $cacheKey = "pos:analytics:daily:{$machineId}:{$date->format('Y-m-d')}";

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($machineId, $date) {
            return $this->reportsService->getDailySummary($machineId, $date);
        });

        return PosApiResponse::success($data);
    }

    public function sessions(Request $request)
    {
        // Active sessions: NO cache (real-time)
        $data = $this->reportsService->getActiveSessions();
        
        return PosApiResponse::success($data);
    }

    public function topProducts(Request $request)
    {
        $request->validate([
            'machine_id' => 'nullable|string',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'limit' => 'nullable|integer|max:50',
        ]);

        $machineId = $request->machine_id;
        $from = $request->from ? Carbon::parse($request->from) : null;
        $to = $request->to ? Carbon::parse($request->to) : null;
        $limit = $request->limit ?? 10;

        $dateParams = ($from ? $from->format('Y-m-d') : 'all') . '_' . ($to ? $to->format('Y-m-d') : 'all');
        $machineParam = $machineId ?? 'all';
        $cacheKey = "pos:analytics:top-products:{$machineParam}:{$dateParams}:{$limit}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($machineId, $from, $to, $limit) {
            return $this->reportsService->getTopProducts($machineId, $from, $to, $limit);
        });

        return PosApiResponse::success($data);
    }

    public function periodReport(Request $request)
    {
        $request->validate([
            'machine_id' => 'nullable|string',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'group_by' => 'nullable|in:day,week,month',
        ]);

        $machineId = $request->machine_id;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);
        $groupBy = $request->group_by ?? 'day';

        $machineParam = $machineId ?? 'all';
        $cacheKey = "pos:analytics:period:{$machineParam}:{$from->format('Y-m-d')}_{$to->format('Y-m-d')}:{$groupBy}";

        $data = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($machineId, $from, $to, $groupBy) {
            return $this->reportsService->getApiPeriodReport($machineId, $from, $to, $groupBy);
        });

        return PosApiResponse::success($data);
    }

    public function lowStock(Request $request)
    {
        $request->validate([
            'threshold' => 'nullable|integer|min:0',
        ]);

        $threshold = $request->threshold ?? 5;
        $cacheKey = "pos:analytics:low-stock:{$threshold}";

        $data = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($threshold) {
            return $this->reportsService->getLowStockAlerts($threshold);
        });

        return PosApiResponse::success($data);
    }

    public function export(Request $request)
    {
        $request->validate([
            'machine_id' => 'nullable|string',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
            'format' => 'required|in:csv,excel',
        ]);

        $machineId = $request->machine_id;
        $from = Carbon::parse($request->from);
        $to = Carbon::parse($request->to);
        $format = $request->format;

        // Fetch data for export (using existing export data structure from Service)
        $data = $this->reportsService->exportSessionsData($from, $to);
        
        // If machine ID filter is requested, filter the collection
        if ($machineId) {
            $data = collect($data)->where('machine_id', $machineId)->values()->toArray();
        }

        $headings = [
            'Session ID', 'Machine ID', 'Operator Name', 'Opened At', 'Closed At',
            'Duration (Minutes)', 'Opening Cash', 'Closing Cash', 'Expected Cash',
            'Cash Difference', 'Sales Count', 'Total Sales Amount', 'Notes'
        ];

        $export = new PosReportExport($data, $headings);

        $filename = "pos_report_{$from->format('Ymd')}_{$to->format('Ymd')}.";
        
        if ($format === 'excel') {
            return Excel::download($export, $filename . 'xlsx', \Maatwebsite\Excel\Excel::XLSX);
        }

        return Excel::download($export, $filename . 'csv', \Maatwebsite\Excel\Excel::CSV);
    }
}

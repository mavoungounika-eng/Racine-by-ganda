<?php

namespace App\Services\Pos;

use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PosReportsService - Rapports et analytics POS
 *
 * Fournit:
 * - KPIs ventes (total, count, moyenne)
 * - Analyse paiements par méthode
 * - Discrepancies tracking
 * - Performance caissiers
 */
class PosReportsService
{
    /**
     * Rapport journalier POS
     */
    public function getDailyReport(\DateTime $date): array
    {
        $sessions = PosSession::whereDate('opened_at', $date)
            ->where('status', 'closed')
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'sessions_count' => $sessions->count(),
            'sessions_total_sales' => $sessions->sum(fn($s) => $s->sales()->sum('total_amount')),
            'total_opening_cash' => $sessions->sum('opening_cash'),
            'total_closing_cash' => $sessions->sum('closing_cash'),
            'total_expected_cash' => $sessions->sum('expected_cash'),
            'total_cash_difference' => $sessions->sum('cash_difference'),
            'discrepancies' => $sessions->filter(fn($s) => abs($s->cash_difference) >= 1.00)->count(),
            'payment_methods' => $this->analyzePaymentMethods($sessions),
            'performance' => $this->analyzeOperatorPerformance($sessions),
        ];
    }

    /**
     * Rapport période (semaine, mois)
     */
    public function getPeriodReport(\DateTime $startDate, \DateTime $endDate): array
    {
        $sessions = PosSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->get();

        $dailyData = [];
        $currentDate = clone $startDate;

        while ($currentDate <= $endDate) {
            $dailyData[$currentDate->format('Y-m-d')] = $this->getDailyReport($currentDate);
            $currentDate->addDay();
        }

        return [
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'days' => count($dailyData),
            'total_sessions' => $sessions->count(),
            'total_sales' => $sessions->sum(fn($s) => $s->sales()->sum('total_amount')),
            'average_session_sales' => $sessions->count() > 0 ? $sessions->sum(fn($s) => $s->sales()->sum('total_amount')) / $sessions->count() : 0,
            'total_cash_discrepancies' => $sessions->sum('cash_difference'),
            'discrepancy_rate' => $sessions->count() > 0 ? ($sessions->filter(fn($s) => abs($s->cash_difference) >= 1.00)->count() / $sessions->count()) * 100 : 0,
            'daily_data' => $dailyData,
        ];
    }

    /**
     * Analyser paiements par méthode
     */
    private function analyzePaymentMethods(Collection $sessions): array
    {
        $paymentStats = [];
        $paymentMethods = ['cash', 'card', 'mobile_money', 'mixed'];

        foreach ($paymentMethods as $method) {
            $sales = $sessions->flatMap(fn($s) => $s->sales)
                ->where('payment_method', $method);

            $paymentStats[$method] = [
                'count' => $sales->count(),
                'total' => $sales->sum('total_amount'),
                'average' => $sales->count() > 0 ? $sales->sum('total_amount') / $sales->count() : 0,
            ];
        }

        return $paymentStats;
    }

    /**
     * Analyser performance opérateurs
     */
    private function analyzeOperatorPerformance(Collection $sessions): array
    {
        $operatorStats = [];

        foreach ($sessions as $session) {
            $userId = $session->opened_by;
            $operator = $session->opener?->name ?? "Unknown (ID: {$userId})";

            if (!isset($operatorStats[$operator])) {
                $operatorStats[$operator] = [
                    'sessions' => 0,
                    'total_sales' => 0,
                    'discrepancies' => 0,
                    'discrepancy_total' => 0,
                ];
            }

            $operatorStats[$operator]['sessions']++;
            $operatorStats[$operator]['total_sales'] += $session->sales()->sum('total_amount');

            if (abs($session->cash_difference) >= 1.00) {
                $operatorStats[$operator]['discrepancies']++;
                $operatorStats[$operator]['discrepancy_total'] += $session->cash_difference;
            }
        }

        // Calculer discrepancy rate
        foreach ($operatorStats as &$stats) {
            $stats['discrepancy_rate'] = $stats['sessions'] > 0
                ? ($stats['discrepancies'] / $stats['sessions']) * 100
                : 0;
            $stats['avg_discrepancy'] = $stats['discrepancies'] > 0
                ? $stats['discrepancy_total'] / $stats['discrepancies']
                : 0;
        }

        return $operatorStats;
    }

    /**
     * Rapport discrepancies
     */
    public function getDiscrepancyReport(\DateTime $startDate, \DateTime $endDate, float $minThreshold = 1.00): array
    {
        $sessions = PosSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->whereRaw('ABS(cash_difference) >= ?', [$minThreshold])
            ->get();

        return [
            'period' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}",
            'threshold' => $minThreshold,
            'discrepancies_count' => $sessions->count(),
            'total_discrepancy_amount' => $sessions->sum('cash_difference'),
            'average_discrepancy' => $sessions->count() > 0 ? $sessions->sum('cash_difference') / $sessions->count() : 0,
            'max_discrepancy' => $sessions->max('cash_difference'),
            'min_discrepancy' => $sessions->min('cash_difference'),
            'details' => $sessions->map(function ($session) {
                return [
                    'session_id' => $session->id,
                    'machine_id' => $session->machine_id,
                    'operator' => $session->opener?->name,
                    'opened_at' => $session->opened_at,
                    'closed_at' => $session->closed_at,
                    'expected_cash' => $session->expected_cash,
                    'actual_cash' => $session->closing_cash,
                    'difference' => $session->cash_difference,
                    'notes' => $session->notes,
                ];
            })->toArray(),
        ];
    }

    /**
     * Export données brutes (CSV-ready)
     */
    public function exportSessionsData(\DateTime $startDate, \DateTime $endDate): array
    {
        $sessions = PosSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->with('opener', 'sales')
            ->get();

        return $sessions->map(function ($session) {
            return [
                'session_id' => $session->id,
                'machine_id' => $session->machine_id,
                'operator_name' => $session->opener?->name,
                'opened_at' => $session->opened_at->toIso8601String(),
                'closed_at' => $session->closed_at->toIso8601String(),
                'duration_minutes' => $session->opened_at->diffInMinutes($session->closed_at),
                'opening_cash' => $session->opening_cash,
                'closing_cash' => $session->closing_cash,
                'expected_cash' => $session->expected_cash,
                'cash_difference' => $session->cash_difference,
                'sales_count' => $session->sales()->count(),
                'total_sales_amount' => $session->sales()->sum('total_amount'),
                'notes' => $session->notes,
            ];
        })->toArray();
    }

    /**
     * API: Get Daily Summary for a specific machine
     */
    public function getDailySummary(string $machineId, Carbon $date): array
    {
        $sessions = PosSession::where('machine_id', $machineId)
            ->whereDate('opened_at', $date)
            ->with(['sales' => function ($query) {
                $query->where('status', PosSale::STATUS_FINALIZED);
            }])
            ->get();

        $activeSession = $sessions->firstWhere('status', PosSession::STATUS_OPEN);
        $sales = $sessions->flatMap->sales;
        $cancelledCount = PosSale::whereIn('session_id', $sessions->pluck('id'))
            ->where('status', PosSale::STATUS_CANCELLED)
            ->count();

        $totalRevenue = $sales->sum('total_amount');
        $transactionCount = $sales->count();

        return [
            'total_revenue' => (float) $totalRevenue,
            'transaction_count' => $transactionCount,
            'average_basket' => $transactionCount > 0 ? (float) ($totalRevenue / $transactionCount) : 0.0,
            'cash_total' => (float) $sales->where('payment_method', PosSale::PAYMENT_CASH)->sum('total_amount'),
            'card_total' => (float) $sales->where('payment_method', PosSale::PAYMENT_CARD)->sum('total_amount'),
            'mobile_total' => (float) $sales->where('payment_method', PosSale::PAYMENT_MOBILE)->sum('total_amount'),
            'cancelled_count' => $cancelledCount,
            'active_session' => $activeSession !== null,
        ];
    }

    /**
     * API: Get all currently open sessions
     */
    public function getActiveSessions(): \Illuminate\Support\Collection
    {
        $sessions = PosSession::where('status', PosSession::STATUS_OPEN)
            ->with(['opener', 'sales' => function ($query) {
                $query->where('status', PosSale::STATUS_FINALIZED);
            }])
            ->get();

        return $sessions->map(function ($session) {
            $sales = $session->sales;
            return [
                'session_id' => $session->id,
                'machine_id' => $session->machine_id,
                'operator_name' => $session->opener?->name ?? 'Unknown',
                'opened_at' => $session->opened_at->toIso8601String(),
                'duration_minutes' => $session->opened_at->diffInMinutes(now()),
                'sales_count' => $sales->count(),
                'revenue_so_far' => (float) $sales->sum('total_amount'),
            ];
        });
    }

    /**
     * API: Get Top Products by revenue and quantity
     */
    public function getTopProducts(?string $machineId = null, ?Carbon $from = null, ?Carbon $to = null, int $limit = 10): \Illuminate\Support\Collection
    {
        $query = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('pos_sales', 'orders.id', '=', 'pos_sales.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('pos_sales.status', PosSale::STATUS_FINALIZED)
            ->select(
                'products.id',
                'products.title as name',
                DB::raw('SUM(order_items.quantity) as quantity_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as revenue'),
                DB::raw('COUNT(DISTINCT pos_sales.id) as transaction_count')
            )
            ->groupBy('products.id', 'products.title');

        if ($machineId) {
            $query->where('pos_sales.machine_id', $machineId);
        }
        if ($from) {
            $query->where('pos_sales.created_at', '>=', $from);
        }
        if ($to) {
            $query->where('pos_sales.created_at', '<=', $to);
        }

        return $query->orderByDesc('revenue')->limit($limit)->get()->map(function ($item) {
            return [
                'product_id' => $item->id,
                'name' => $item->name,
                'quantity_sold' => (int) $item->quantity_sold,
                'revenue' => (float) $item->revenue,
                'transaction_count' => (int) $item->transaction_count,
            ];
        });
    }

    /**
     * API: Get Low Stock Alerts
     */
    public function getLowStockAlerts(int $threshold = 5): \Illuminate\Support\Collection
    {
        // Assuming products table has 'stock' and 'sku' columns.
        // Also assuming 'last_sold_at' can be derived or is updated. If not, we join with latest sale.
        return DB::table('products')
            ->where('stock', '<=', $threshold)
            ->where('is_active', true) // assuming active products only
            ->select('id as product_id', 'title as name', 'stock', DB::raw("$threshold as threshold"))
            ->get()
            ->map(function ($product) {
                // To get last_sold_at, we do a subquery or separate query (for performance, doing it per item or with a join is possible, but simple approach here)
                $lastSale = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->join('pos_sales', 'orders.id', '=', 'pos_sales.order_id')
                    ->where('order_items.product_id', $product->product_id)
                    ->where('pos_sales.status', PosSale::STATUS_FINALIZED)
                    ->orderByDesc('pos_sales.created_at')
                    ->first(['pos_sales.created_at']);
                
                return [
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'sku' => $product->sku ?? null,
                    'stock' => (int) $product->stock,
                    'threshold' => (int) $product->threshold,
                    'last_sold_at' => $lastSale ? Carbon::parse($lastSale->created_at)->toIso8601String() : null,
                ];
            });
    }

    /**
     * API: Get Period Report (extended for API)
     */
    public function getApiPeriodReport(?string $machineId = null, Carbon $from, Carbon $to, string $groupBy = 'day'): array
    {
        $query = PosSession::whereBetween('opened_at', [$from, $to])
            ->where('status', 'closed')
            ->with(['sales' => function ($q) {
                $q->where('status', PosSale::STATUS_FINALIZED);
            }]);

        if ($machineId) {
            $query->where('machine_id', $machineId);
        }

        $sessions = $query->get();
        $sales = $sessions->flatMap->sales;

        // Grouping data
        $revenueByPeriod = [];
        $dateFormat = $groupBy === 'month' ? 'Y-m' : ($groupBy === 'week' ? 'Y-\WW' : 'Y-m-d');

        foreach ($sales as $sale) {
            $dateKey = Carbon::parse($sale->created_at)->format($dateFormat);
            if (!isset($revenueByPeriod[$dateKey])) {
                $revenueByPeriod[$dateKey] = 0;
            }
            $revenueByPeriod[$dateKey] += $sale->total_amount;
        }

        return [
            'total_revenue' => (float) $sales->sum('total_amount'),
            'total_transactions' => $sales->count(),
            'revenue_by_period' => $revenueByPeriod,
            'payment_method_breakdown' => [
                'cash' => (float) $sales->where('payment_method', PosSale::PAYMENT_CASH)->sum('total_amount'),
                'card' => (float) $sales->where('payment_method', PosSale::PAYMENT_CARD)->sum('total_amount'),
                'mobile_money' => (float) $sales->where('payment_method', PosSale::PAYMENT_MOBILE)->sum('total_amount'),
                'mixed' => (float) $sales->where('payment_method', PosSale::PAYMENT_MIXED)->sum('total_amount'),
            ],
            'grouped_sales_data' => $revenueByPeriod, // Alias for API response consistency
        ];
    }
}

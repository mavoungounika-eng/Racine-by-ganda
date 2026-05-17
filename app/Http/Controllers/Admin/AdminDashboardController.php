<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private OrderRepository $orderRepository
    ) {}

    public function index()
    {
        try {
            $data = $this->dashboardService->getData();

            try {
                $matStats = \Modules\ERP\Models\ErpRawMaterial::selectRaw(
                    'COUNT(CASE WHEN current_stock < min_stock_alert THEN 1 END) as low_stock,' .
                    'COALESCE(SUM(current_stock * unit_price), 0) as stock_value'
                )->first();
                $data['erp'] = [
                    'low_stock'      => (int) ($matStats->low_stock ?? 0),
                    'pending_orders' => \Modules\ERP\Models\ErpPurchase::where('status', 'ordered')->count(),
                    'stock_value'    => (float) ($matStats->stock_value ?? 0),
                ];
            } catch (\Throwable $e) {
                $data['erp'] = ['low_stock' => 0, 'pending_orders' => 0, 'stock_value' => 0];
            }

            return view('admin.dashboard.index', $data);
        } catch (\Exception $e) {
            if (app()->environment('testing')) {
                throw $e;
            }
            Log::error('Dashboard error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return view('admin.dashboard.index', [
                'error'               => 'Impossible de charger le dashboard. Veuillez réessayer.',
                'global_state'        => [],
                'alerts'              => [],
                'commercial_activity' => [],
                'marketplace'         => [],
                'operations'          => [],
                'trends'              => [],
                'last_updated'        => now()->format('H:i'),
                'erp'                 => ['low_stock' => 0, 'pending_orders' => 0, 'stock_value' => 0],
            ]);
        }
    }

    public function kpis(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');

        $start = match ($period) {
            'week'  => now()->subDays(7)->startOfDay(),
            'month' => now()->subDays(30)->startOfDay(),
            default => now()->startOfDay(),
        };

        $prevStart = match ($period) {
            'week'  => now()->subDays(14)->startOfDay(),
            'month' => now()->subDays(60)->startOfDay(),
            default => now()->subDay()->startOfDay(),
        };
        $prevEnd = $start;

        $revenueNow  = (float) Order::whereBetween('created_at', [$start, now()])->whereIn('status', ['completed', 'processing'])->sum('total_amount');
        $revenuePrev = (float) Order::whereBetween('created_at', [$prevStart, $prevEnd])->whereIn('status', ['completed', 'processing'])->sum('total_amount');

        $ordersNow  = Order::whereBetween('created_at', [$start, now()])->count();
        $ordersPrev = Order::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $clientsNow  = User::whereBetween('created_at', [$start, now()])->where('role', 'client')->count();
        $clientsPrev = User::whereBetween('created_at', [$prevStart, $prevEnd])->where('role', 'client')->count();

        $pendingOrders = Order::whereIn('status', ['pending', 'processing'])->count();

        $changePct = fn($now, $prev) => $prev > 0 ? round(($now - $prev) / $prev * 100, 1) : ($now > 0 ? 100 : 0);

        return response()->json([
            'period'  => $period,
            'revenue' => [
                'value'      => $revenueNow,
                'formatted'  => number_format($revenueNow, 0, ',', ' ') . ' XAF',
                'change_pct' => $changePct($revenueNow, $revenuePrev),
            ],
            'orders' => [
                'value'      => $ordersNow,
                'change_pct' => $changePct($ordersNow, $ordersPrev),
            ],
            'clients' => [
                'value'      => $clientsNow,
                'change_pct' => $changePct($clientsNow, $clientsPrev),
            ],
            'pending_orders' => $pendingOrders,
            'stock_alerts'   => \Modules\ERP\Models\ErpRawMaterial::whereColumn('current_stock', '<', 'min_stock_alert')->count(),
        ]);
    }

    public function chartData(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 30), 90);

        $raw = $this->orderRepository->getRevenueTrend($days);

        $indexed = [];
        foreach ($raw as $row) {
            $indexed[$row['date']] = (float) $row['revenue'];
        }

        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d/m');
            $values[] = $indexed[$date] ?? 0;
        }

        return response()->json([
            'labels'   => $labels,
            'datasets' => [
                [
                    'label' => 'Chiffre d\'affaires (XAF)',
                    'data'  => $values,
                ],
            ],
        ]);
    }

    public function refresh()
    {
        try {
            $this->dashboardService->refresh();

            return redirect()->route('admin.dashboard')
                ->with('success', 'Dashboard rafraîchi avec succès');
        } catch (\Exception $e) {
            Log::error('Dashboard refresh error: ' . $e->getMessage());

            return redirect()->route('admin.dashboard')
                ->with('error', 'Erreur lors du rafraîchissement');
        }
    }
}

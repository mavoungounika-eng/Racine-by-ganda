<?php

namespace Modules\Analytics\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpStockMovement;
use Modules\CRM\Models\CrmOpportunity;
use Modules\CRM\Models\CrmContact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    /**
     * Récupère les KPIs principaux du tableau de bord
     */
    public function getMainKPIs(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Revenus
        $revenueThisMonth = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->sum('total_amount');

        $revenueLastMonth = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->sum('total_amount');

        $revenueGrowth = $revenueLastMonth > 0 
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : 100;

        // Commandes
        $ordersThisMonth = Order::where('created_at', '>=', $thisMonth)->count();
        $ordersLastMonth = Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])->count();
        $ordersGrowth = $ordersLastMonth > 0 
            ? round((($ordersThisMonth - $ordersLastMonth) / $ordersLastMonth) * 100, 1)
            : 100;

        // Panier moyen
        $avgCart = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->avg('total_amount') ?? 0;

        // Nouveaux clients
        $newClients = User::where('role', 'client')
            ->where('created_at', '>=', $thisMonth)
            ->count();

        // Taux de conversion (commandes payées / total commandes)
        $totalOrders = Order::where('created_at', '>=', $thisMonth)->count();
        $paidOrders = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $thisMonth)
            ->count();
        $conversionRate = $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 1) : 0;

        return [
            'revenue' => [
                'value' => $revenueThisMonth,
                'growth' => $revenueGrowth,
                'formatted' => number_format($revenueThisMonth, 0, ',', ' ') . ' FCFA',
            ],
            'orders' => [
                'value' => $ordersThisMonth,
                'growth' => $ordersGrowth,
            ],
            'avg_cart' => [
                'value' => $avgCart,
                'formatted' => number_format($avgCart, 0, ',', ' ') . ' FCFA',
            ],
            'new_clients' => $newClients,
            'conversion_rate' => $conversionRate,
            'today_revenue' => Order::where('payment_status', 'paid')
                ->whereDate('created_at', $today)
                ->sum('total_amount'),
            'today_orders' => Order::whereDate('created_at', $today)->count(),
        ];
    }

    /**
     * Données pour le graphique des revenus (30 derniers jours)
     */
    public function getRevenueChart(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $revenues = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::parse($date)->format('d/m');
            $data[] = $revenues->get($date)?->total ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Revenus (FCFA)',
                    'data' => $data,
                    'borderColor' => '#ED5F1E',
                    'backgroundColor' => 'rgba(237, 95, 30, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Données pour le graphique des commandes par statut
     */
    public function getOrdersStatusChart(): array
    {
        $statuses = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $statusLabels = [
            'pending' => 'En attente',
            'processing' => 'En préparation',
            'shipped' => 'Expédiée',
            'completed' => 'Livrée',
            'cancelled' => 'Annulée',
        ];

        $colors = [
            'pending' => '#FFC107',
            'processing' => '#17A2B8',
            'shipped' => '#007BFF',
            'completed' => '#28A745',
            'cancelled' => '#DC3545',
        ];

        return [
            'labels' => array_map(fn($s) => $statusLabels[$s] ?? $s, array_keys($statuses)),
            'datasets' => [
                [
                    'data' => array_values($statuses),
                    'backgroundColor' => array_map(fn($s) => $colors[$s] ?? '#6C757D', array_keys($statuses)),
                ],
            ],
        ];
    }

    /**
     * Top 5 des produits les plus vendus
     */
    public function getTopProducts(int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('products.id, products.title, SUM(order_items.quantity) as total_sold, SUM(order_items.quantity * order_items.price) as revenue')
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'total_sold' => (int) $p->total_sold,
                'revenue' => (float) $p->revenue,
                'revenue_formatted' => number_format($p->revenue, 0, ',', ' ') . ' FCFA',
            ])
            ->toArray();
    }

    /**
     * Revenus par catégorie
     */
    public function getRevenueByCategory(): array
    {
        $data = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('categories.name, SUM(order_items.quantity * order_items.price) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get();

        $colors = ['#ED5F1E', '#FFB800', '#160D0C', '#2A1A18', '#4A3A38', '#6A5A58'];

        return [
            'labels' => $data->pluck('name')->toArray(),
            'datasets' => [
                [
                    'data' => $data->pluck('revenue')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                ],
            ],
        ];
    }

    /**
     * Insights automatiques générés par l'IA analytique
     */
    public function getSmartInsights(): array
    {
        $insights = [];

        // Insight 1: Produit Star
        $topProduct = $this->getTopProducts(1)[0] ?? null;
        if ($topProduct) {
            $insights[] = [
                'type' => 'success',
                'icon' => '⭐',
                'title' => 'Produit Star',
                'message' => "'{$topProduct['title']}' est votre best-seller avec {$topProduct['total_sold']} ventes!",
            ];
        }

        // Insight 2: Alerte stock
        $lowStockCount = Product::where('stock', '<', 5)->where('stock', '>', 0)->count();
        $outOfStockCount = Product::where('stock', '<=', 0)->count();
        
        if ($outOfStockCount > 0) {
            $insights[] = [
                'type' => 'danger',
                'icon' => '🚨',
                'title' => 'Rupture de Stock',
                'message' => "{$outOfStockCount} produit(s) en rupture! Passez commande urgente.",
            ];
        } elseif ($lowStockCount > 0) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'title' => 'Stock Faible',
                'message' => "{$lowStockCount} produit(s) avec stock < 5 unités.",
            ];
        }

        // Insight 3: Performance du jour
        $todayRevenue = Order::where('payment_status', 'paid')
            ->whereDate('created_at', Carbon::today())
            ->sum('total_amount');
        
        $avgDailyRevenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('AVG(daily_total) as avg')
            ->fromSub(
                Order::where('payment_status', 'paid')
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->selectRaw('DATE(created_at) as date, SUM(total_amount) as daily_total')
                    ->groupBy('date'),
                'daily'
            )
            ->value('avg') ?? 0;

        if ($todayRevenue > $avgDailyRevenue * 1.2) {
            $insights[] = [
                'type' => 'success',
                'icon' => '🔥',
                'title' => 'Journée Exceptionnelle',
                'message' => "Revenus aujourd'hui +20% au-dessus de la moyenne!",
            ];
        } elseif ($todayRevenue < $avgDailyRevenue * 0.5 && Carbon::now()->hour >= 15) {
            $insights[] = [
                'type' => 'warning',
                'icon' => '📉',
                'title' => 'Activité Faible',
                'message' => "Revenus en dessous de la moyenne. Pensez à une promo flash!",
            ];
        }

        // Insight 4: Opportunités CRM
        try {
            $hotOpportunities = CrmOpportunity::where('status', 'negotiation')
                ->where('expected_amount', '>', 50000)
                ->count();
            
            if ($hotOpportunities > 0) {
                $insights[] = [
                    'type' => 'info',
                    'icon' => '💰',
                    'title' => 'Opportunités Chaudes',
                    'message' => "{$hotOpportunities} opportunité(s) en négociation > 50K FCFA!",
                ];
            }
        } catch (\Exception $e) {
            // CRM module might not be active
        }

        // Insight 5: Nouveaux clients
        $newClientsToday = User::where('role', 'client')
            ->whereDate('created_at', Carbon::today())
            ->count();
        
        if ($newClientsToday >= 3) {
            $insights[] = [
                'type' => 'success',
                'icon' => '🎉',
                'title' => 'Croissance Clients',
                'message' => "{$newClientsToday} nouveaux clients aujourd'hui! Belle acquisition!",
            ];
        }

        return $insights;
    }

    /**
     * Données pour le comparatif mensuel
     */
    public function getMonthlyComparison(): array
    {
        $months = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            $revenue = Order::where('payment_status', 'paid')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('total_amount');

            $orders = Order::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            $months[] = [
                'label' => $month->translatedFormat('M Y'),
                'revenue' => $revenue,
                'orders' => $orders,
            ];
        }

        return [
            'labels' => array_column($months, 'label'),
            'datasets' => [
                [
                    'label' => 'Revenus (FCFA)',
                    'data' => array_column($months, 'revenue'),
                    'backgroundColor' => '#ED5F1E',
                    'borderRadius' => 8,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Commandes',
                    'data' => array_column($months, 'orders'),
                    'backgroundColor' => '#FFB800',
                    'borderRadius' => 8,
                    'yAxisID' => 'y1',
                ],
            ],
        ];
    }

    /**
     * Heures de pointe des ventes
     */
    public function getPeakHours(): array
    {
        $hourlyData = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $labels = [];
        $ordersData = [];
        $revenueData = [];

        for ($h = 0; $h <= 23; $h++) {
            $labels[] = sprintf('%02d:00', $h);
            $ordersData[] = $hourlyData->get($h)?->orders ?? 0;
            $revenueData[] = $hourlyData->get($h)?->revenue ?? 0;
        }

        // Trouver l'heure de pointe
        $peakHour = array_search(max($ordersData), $ordersData);

        return [
            'chart' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Commandes',
                        'data' => $ordersData,
                        'borderColor' => '#ED5F1E',
                        'backgroundColor' => 'rgba(237, 95, 30, 0.2)',
                        'fill' => true,
                    ],
                ],
            ],
            'peak_hour' => sprintf('%02d:00 - %02d:00', $peakHour, ($peakHour + 1) % 24),
            'peak_orders' => max($ordersData),
        ];
    }
}


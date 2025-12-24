<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Contrôleur pour le dashboard administrateur
 * 
 * Gère l'affichage des statistiques, graphiques et activités récentes
 */
class AdminDashboardController extends Controller
{
    /**
     * Afficher le dashboard admin avec statistiques complètes.
     * 
     * @return View Vue du dashboard avec statistiques, graphiques et activités récentes
     */
    public function index(): View
    {
        // ============================================
        // STATISTIQUES CLÉS
        // ============================================
        
        // ✅ OPTIMISATION : Cache pour toutes les stats (une seule requête agrégée)
        $statsCacheKey = 'admin.dashboard.stats';
        $stats = Cache::remember($statsCacheKey, 600, function () {
            return [
                // Ventes du mois
                'monthly_sales' => $this->getMonthlySales(),
                'monthly_sales_evolution' => $this->getMonthlySalesEvolution(),
                
                // Commandes
                'monthly_orders' => $this->getMonthlyOrdersCount(),
                'pending_orders' => $this->getPendingOrdersCount(),
                
                // Clients
                'new_clients_month' => $this->getNewClientsThisMonth(),
                'total_clients' => User::whereHas('roleRelation', function($q) {
                    $q->where('slug', 'client');
                })->count(),
                
                // Produits
                'total_products' => Product::count(),
                'low_stock_products' => Product::where('stock', '<', 10)->count(),
                
                // Créateurs
                'total_creators' => CreatorProfile::where('is_active', true)->count(),
                'verified_creators' => CreatorProfile::where('is_active', true)
                    ->where('is_verified', true)
                    ->count(),
            ];
        });

        // ============================================
        // DONNÉES POUR GRAPHIQUES CHART.JS
        // ============================================
        
        $chartData = [
            // Ventes par mois (12 derniers mois)
            'salesByMonth' => $this->getSalesByMonth(),
            
            // Commandes par mois (12 derniers mois)
            'ordersByMonth' => $this->getOrdersByMonth(),
            
            // Top 10 produits vendus
            'topProducts' => $this->getTopProducts(),
            
            // Répartition commandes par statut
            'ordersByStatus' => $this->getOrdersByStatus(),
            
            // Nouveaux clients par mois (12 derniers mois)
            'newClientsByMonth' => $this->getNewClientsByMonth(),
        ];

        // ============================================
        // ACTIVITÉ RÉCENTE
        // ============================================
        
        $recentActivity = [
            // 5 dernières commandes
            'recent_orders' => Order::with(['user', 'items.product'])
                ->latest()
                ->take(5)
                ->get(),
            
            // 5 nouveaux clients
            'new_users' => User::whereHas('roleRelation', function($q) {
                    $q->where('slug', 'client');
                })
                ->latest()
                ->take(5)
                ->get(),
            
            // 5 produits récents
            'recent_products' => Product::with(['category', 'creator'])
                ->latest()
                ->take(5)
                ->get(),
            
            // 5 derniers paiements réussis
            'recent_payments' => Payment::where('status', 'paid')
                ->with(['order.user'])
                ->latest()
                ->take(5)
                ->get(),
        ];

        return view('admin.dashboard', compact('stats', 'chartData', 'recentActivity'));
    }

    /**
     * Calculer les ventes du mois en cours.
     * Cache: 15 minutes
     */
    private function getMonthlySales(): float
    {
        $cacheKey = 'admin.dashboard.monthly_sales_' . now()->format('Y-m');
        
        return Cache::remember($cacheKey, 900, function () {
            return Payment::where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');
        });
    }

    /**
     * Calculer l'évolution des ventes par rapport au mois précédent.
     * ✅ OPTIMISATION : Cache pour previousMonth
     */
    private function getMonthlySalesEvolution(): float
    {
        $currentMonth = $this->getMonthlySales();
        
        $previousMonthCacheKey = 'admin.dashboard.monthly_sales_' . now()->subMonth()->format('Y-m');
        $previousMonth = Cache::remember($previousMonthCacheKey, 900, function () {
            return Payment::where('status', 'paid')
                ->whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->sum('amount');
        });

        if ($previousMonth == 0) {
            return $currentMonth > 0 ? 100 : 0;
        }

        return round((($currentMonth - $previousMonth) / $previousMonth) * 100, 2);
    }

    /**
     * Compter les commandes du mois.
     * ✅ OPTIMISATION : Utilisé dans cache stats globales
     */
    private function getMonthlyOrdersCount(): int
    {
        return Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Compter les commandes en attente.
     * ✅ OPTIMISATION : Utilisé dans cache stats globales
     */
    private function getPendingOrdersCount(): int
    {
        return Order::where('status', 'pending')->count();
    }

    /**
     * Compter les nouveaux clients ce mois.
     * ✅ OPTIMISATION : Utilisé dans cache stats globales
     */
    private function getNewClientsThisMonth(): int
    {
        return User::whereHas('roleRelation', function($q) {
                $q->where('slug', 'client');
            })
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Obtenir les ventes par mois (12 derniers mois).
     * ✅ OPTIMISATION : Une seule requête agrégée au lieu de 12 requêtes
     * Cache: 15 minutes
     */
    private function getSalesByMonth(): array
    {
        $cacheKey = 'admin.dashboard.sales_by_month';
        
        return Cache::remember($cacheKey, 900, function () {
            // Calculer la date de début (12 mois en arrière)
            $startDate = now()->subMonths(11)->startOfMonth();
            
            // ✅ Une seule requête agrégée pour tous les mois
            $monthlySales = Payment::where('status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    DATE_FORMAT(created_at, "%b %Y") as month_label,
                    MONTH(created_at) as month,
                    YEAR(created_at) as year,
                    SUM(amount) as total
                ')
                ->groupBy('year', 'month', 'month_label')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });
            
            // Générer les labels et données pour les 12 derniers mois
            $months = [];
            $sales = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-m');
                $months[] = $date->format('M Y');
                
                $monthlySale = $monthlySales->get($monthKey);
                $sales[] = round($monthlySale->total ?? 0, 2);
            }

            return [
                'labels' => $months,
                'data' => $sales,
            ];
        });
    }

    /**
     * Obtenir le nombre de commandes par mois (12 derniers mois).
     * ✅ OPTIMISATION : Une seule requête agrégée au lieu de 12 requêtes
     * Cache: 15 minutes
     */
    private function getOrdersByMonth(): array
    {
        $cacheKey = 'admin.dashboard.orders_by_month';
        
        return Cache::remember($cacheKey, 900, function () {
            // Calculer la date de début (12 mois en arrière)
            $startDate = now()->subMonths(11)->startOfMonth();
            
            // ✅ Une seule requête agrégée pour tous les mois
            $monthlyOrders = Order::where('created_at', '>=', $startDate)
                ->selectRaw('
                    DATE_FORMAT(created_at, "%b %Y") as month_label,
                    MONTH(created_at) as month,
                    YEAR(created_at) as year,
                    COUNT(*) as total
                ')
                ->groupBy('year', 'month', 'month_label')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });
            
            // Générer les labels et données pour les 12 derniers mois
            $months = [];
            $orders = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-m');
                $months[] = $date->format('M Y');
                
                $monthlyOrder = $monthlyOrders->get($monthKey);
                $orders[] = (int) ($monthlyOrder->total ?? 0);
            }

            return [
                'labels' => $months,
                'data' => $orders,
            ];
        });
    }

    /**
     * Obtenir les 10 produits les plus vendus.
     * Cache: 15 minutes
     */
    private function getTopProducts(): array
    {
        $cacheKey = 'admin.dashboard.top_products';
        
        return Cache::remember($cacheKey, 900, function () {
            $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
                ->groupBy('product_id')
                ->orderByDesc('total_sold')
                ->take(10)
                ->with('product')
                ->get();

            $labels = [];
            $data = [];

            foreach ($topProducts as $item) {
                $labels[] = $item->product->title ?? 'Produit supprimé';
                $data[] = $item->total_sold;
            }

            return [
                'labels' => $labels,
                'data' => $data,
            ];
        });
    }

    /**
     * Obtenir la répartition des commandes par statut.
     * Cache: 10 minutes (statuts changent plus fréquemment)
     */
    private function getOrdersByStatus(): array
    {
        $cacheKey = 'admin.dashboard.orders_by_status';
        
        return Cache::remember($cacheKey, 600, function () {
            $statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
            $labels = [
                'pending' => 'En attente',
                'paid' => 'Payée',
                'shipped' => 'Expédiée',
                'delivered' => 'Livrée',
                'cancelled' => 'Annulée',
            ];
            
            $data = [];
            $labelsFr = [];

            foreach ($statuses as $status) {
                $count = Order::where('status', $status)->count();
                $data[] = $count;
                $labelsFr[] = $labels[$status];
            }

            return [
                'labels' => $labelsFr,
                'data' => $data,
            ];
        });
    }

    /**
     * Obtenir le nombre de nouveaux clients par mois (12 derniers mois).
     * ✅ OPTIMISATION : Une seule requête agrégée au lieu de 12 requêtes
     * Cache: 15 minutes
     */
    private function getNewClientsByMonth(): array
    {
        $cacheKey = 'admin.dashboard.new_clients_by_month';
        
        return Cache::remember($cacheKey, 900, function () {
            // Calculer la date de début (12 mois en arrière)
            $startDate = now()->subMonths(11)->startOfMonth();
            
            // ✅ Une seule requête agrégée pour tous les mois
            $monthlyClients = User::whereHas('roleRelation', function($q) {
                    $q->where('slug', 'client');
                })
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    DATE_FORMAT(created_at, "%b %Y") as month_label,
                    MONTH(created_at) as month,
                    YEAR(created_at) as year,
                    COUNT(*) as total
                ')
                ->groupBy('year', 'month', 'month_label')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });
            
            // Générer les labels et données pour les 12 derniers mois
            $months = [];
            $clients = [];

            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $monthKey = $date->format('Y-m');
                $months[] = $date->format('M Y');
                
                $monthlyClient = $monthlyClients->get($monthKey);
                $clients[] = (int) ($monthlyClient->total ?? 0);
            }

            return [
                'labels' => $months,
                'data' => $clients,
            ];
        });
    }
}

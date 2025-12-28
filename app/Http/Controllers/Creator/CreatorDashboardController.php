<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Contrôleur pour le dashboard créateur
 * 
 * Gère l'affichage des statistiques, produits et commandes du créateur
 */
class CreatorDashboardController extends Controller
{
    /**
     * Afficher le dashboard créateur avec statistiques complètes.
     * 
     * @return View Vue du dashboard avec statistiques, produits récents et commandes
     */
    public function index(): View
    {
        $user = Auth::user();
        $user->load('creatorProfile.validationChecklist');
        
        $creatorProfile = $user->creatorProfile;
        
        // S'assurer que creatorProfile est chargé pour la vue
        if (!$creatorProfile) {
            $creatorProfile = new \App\Models\CreatorProfile();
            $creatorProfile->brand_name = $user->name;
            $creatorProfile->status = 'active';
        }
        
        // Statistiques
        $stats = [
            'products_count' => Product::where('user_id', $user->id)->count(),
            'active_products_count' => Product::where('user_id', $user->id)
                ->where('is_active', true)
                ->count(),
            'collections_count' => Collection::where('user_id', $user->id)->count(),
            'total_sales' => $this->calculateTotalSales($user->id),
            'monthly_sales' => $this->calculateMonthlySales($user->id),
            'pending_orders' => $this->getPendingOrdersCount($user->id),
        ];

        // Produits récents
        $recentProducts = Product::where('user_id', $user->id)
            ->with(['category', 'collection'])
            ->latest()
            ->take(5)
            ->get();

        // Produits les plus vendus
        $topProducts = $this->getTopSellingProducts($user->id);

        // Données pour graphiques
        $salesData = $this->getSalesChartData($user->id);

        // Commandes récentes du créateur (via OrderItem -> Product)
        $recentOrders = Order::whereHas('items.product', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->with(['items.product'])
        ->latest()
        ->take(5)
        ->get();

        // PHASE 6: Dashboard dynamique selon capability
        $dashboardLayout = $user->getDashboardLayout(); // basic, advanced, premium
        
        // Sélectionner la vue selon le layout
        $viewName = "creator.dashboard.{$dashboardLayout}";
        
        // Si la vue spécifique n'existe pas, fallback vers basic
        if (!view()->exists($viewName)) {
            $viewName = 'creator.dashboard.basic';
        }

        return view($viewName, compact(
            'stats',
            'recentProducts',
            'topProducts',
            'salesData',
            'creatorProfile',
            'recentOrders',
            'user',
            'dashboardLayout'
        ));
    }

    /**
     * Calculer le total des ventes du créateur.
     */
    private function calculateTotalSales(int $userId): float
    {
        return OrderItem::whereHas('product', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('order', function ($query) {
            $query->where('status', 'paid');
        })
        ->sum(DB::raw('price * quantity'));
    }

    /**
     * Calculer les ventes du mois en cours.
     */
    private function calculateMonthlySales(int $userId): float
    {
        return OrderItem::whereHas('product', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('order', function ($query) {
            $query->where('status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        })
        ->sum(DB::raw('price * quantity'));
    }

    /**
     * Compter les commandes en attente.
     */
    private function getPendingOrdersCount(int $userId): int
    {
        return OrderItem::whereHas('product', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('order', function ($query) {
            $query->where('status', 'pending');
        })
        ->distinct('order_id')
        ->count('order_id');
    }

    /**
     * Obtenir les produits les plus vendus.
     */
    private function getTopSellingProducts(int $userId, int $limit = 5): array
    {
        return OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take($limit)
            ->with('product')
            ->get()
            ->toArray();
    }

    /**
     * Obtenir les données pour le graphique des ventes.
     * ✅ OPTIMISATION: Une seule requête agrégée au lieu de 12 requêtes
     */
    private function getSalesChartData(int $userId): array
    {
        // Calculer la date de début (12 mois en arrière)
        $startDate = now()->subMonths(11)->startOfMonth();
        
        // ✅ Une seule requête agrégée pour tous les mois
        $salesByMonth = OrderItem::select(
                DB::raw('YEAR(orders.created_at) as year'),
                DB::raw('MONTH(orders.created_at) as month'),
                DB::raw('SUM(order_items.price * order_items.quantity) as total')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('products.user_id', $userId)
            ->where('orders.status', 'paid')
            ->where('orders.created_at', '>=', $startDate)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        // Construire le tableau avec tous les mois (remplir les mois manquants)
        $months = [];
        $sales = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            $key = $date->format('Y-m');
            
            // Récupérer la valeur ou 0 si le mois n'a pas de ventes
            $sales[] = isset($salesByMonth[$key]) 
                ? round($salesByMonth[$key]->total, 2) 
                : 0;
        }

        return [
            'labels' => $months,
            'data' => $sales,
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminStatsController extends Controller
{
    public function index(): View
    {
        // ✅ OPTIMISATION : Cache pour toutes les stats
        $statsCacheKey = 'admin.stats.global';
        $stats = Cache::remember($statsCacheKey, 600, function () {
            return [
                'total_products' => Product::count(),
                'total_orders' => Order::count(),
                'total_users' => User::count(),
                'total_revenue' => Payment::where('status', 'paid')->sum('amount') ?? 0,
            ];
        });

        // ✅ OPTIMISATION : Top produits avec eager loading (évite N+1)
        $topProductsCacheKey = 'admin.stats.top_products';
        $topProducts = Cache::remember($topProductsCacheKey, 900, function () {
            $topProductsData = DB::table('order_items')
                ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
                ->groupBy('product_id')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->pluck('total_sold', 'product_id');

            // ✅ Charger tous les produits en une seule requête
            $productIds = $topProductsData->keys()->toArray();
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            return $topProductsData->map(function ($totalSold, $productId) use ($products) {
                $product = $products->get($productId);
                if ($product) {
                    $product->total_sold = $totalSold;
                    return $product;
                }
                return null;
            })->filter();
        });

        // ✅ OPTIMISATION : Ventes par mois avec une seule requête agrégée
        $monthlySalesCacheKey = 'admin.stats.monthly_sales';
        $monthlySales = Cache::remember($monthlySalesCacheKey, 900, function () {
            $startDate = now()->subMonths(11)->startOfMonth();
            
            // Une seule requête agrégée pour tous les mois
            $monthlySalesData = Payment::where('status', 'paid')
                ->where('created_at', '>=', $startDate)
                ->selectRaw('
                    DATE_FORMAT(created_at, "%b %Y") as month_label,
                    MONTH(created_at) as month,
                    YEAR(created_at) as year,
                    SUM(amount) as amount
                ')
                ->groupBy('year', 'month', 'month_label')
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->keyBy(function ($item) {
                    return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
                });

            // Générer les données pour les 12 derniers mois
            $result = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->copy()->subMonths($i);
                $monthKey = $date->format('Y-m');
                $monthlySale = $monthlySalesData->get($monthKey);
                
                $result[] = [
                    'month' => $date->format('M Y'),
                    'amount' => $monthlySale->amount ?? 0,
                ];
            }
            
            return $result;
        });

        return view('admin.stats.index', compact('stats', 'topProducts', 'monthlySales'));
    }
}

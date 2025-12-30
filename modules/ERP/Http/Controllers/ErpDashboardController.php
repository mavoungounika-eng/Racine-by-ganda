<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpStock;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpPurchaseItem;
use Modules\ERP\Services\StockAlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Contrôleur du tableau de bord ERP
 * 
 * Gère l'affichage des statistiques et indicateurs clés du module ERP.
 * Les données sont mises en cache pour optimiser les performances.
 * 
 * @package Modules\ERP\Http\Controllers
 */
class ErpDashboardController extends Controller
{
    /**
     * Affiche le tableau de bord ERP avec les statistiques principales
     * 
     * Affiche :
     * - Valorisation du stock
     * - Statistiques des achats du mois
     * - Flux de stock du jour
     * - Produits en stock faible
     * - Top matières premières
     * - Achats récents
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // ✅ Cache des stats (configurable) - TTL optimisé : 15-30 minutes
        $cacheKey = 'erp.dashboard.stats';
        $ttl = config('erp.cache.dashboard_stats_ttl', 900); // 15 minutes par défaut
        $stats = Cache::remember($cacheKey, $ttl, function () {
            // Optimisation: Une seule requête pour toutes les stats du dashboard
            $month = now()->month;
            $year = now()->year;
            $today = now()->toDateString();
            
            // SQLite compatibility: Use different date functions based on driver
            $driver = DB::getDriverName();
            if ($driver === 'sqlite') {
                $monthFunc = "CAST(strftime('%m', purchase_date) AS INTEGER)";
                $yearFunc = "CAST(strftime('%Y', purchase_date) AS INTEGER)";
                $dateFunc = "DATE(created_at)";
                $todayValue = "'$today'";
            } else {
                $monthFunc = "MONTH(purchase_date)";
                $yearFunc = "YEAR(purchase_date)";
                $dateFunc = "DATE(created_at)";
                $todayValue = "CURDATE()";
            }
            
            $result = DB::selectOne("
                SELECT 
                    (SELECT COUNT(*) FROM products) as products_total,
                    (SELECT COUNT(*) FROM products WHERE stock < 5 AND stock > 0) as products_low_stock,
                    (SELECT COUNT(*) FROM products WHERE stock <= 0) as products_out_of_stock,
                    (SELECT COUNT(*) FROM erp_suppliers) as suppliers_total,
                    (SELECT COUNT(*) FROM erp_suppliers WHERE is_active = 1) as suppliers_active,
                    (SELECT COUNT(*) FROM erp_raw_materials) as materials_total,
                    (SELECT COALESCE(SUM(price * stock), 0) FROM products WHERE stock > 0) as stock_value_global,
                    (SELECT COUNT(*) FROM erp_purchases WHERE $monthFunc = ? AND $yearFunc = ?) as purchases_month_count,
                    (SELECT COALESCE(SUM(total_amount), 0) FROM erp_purchases WHERE $monthFunc = ? AND $yearFunc = ?) as purchases_month_sum,
                    (SELECT COALESCE(SUM(quantity), 0) FROM erp_stock_movements WHERE $dateFunc = $todayValue AND type = 'in') as flow_today_in,
                    (SELECT COALESCE(SUM(quantity), 0) FROM erp_stock_movements WHERE $dateFunc = $todayValue AND type = 'out') as flow_today_out,
                    (SELECT COUNT(*) FROM erp_purchases WHERE status = 'ordered') as purchases_pending,
                    (SELECT COUNT(*) FROM erp_purchases WHERE status = 'received' AND $monthFunc = ? AND $yearFunc = ?) as purchases_received
            ", [$month, $year, $month, $year, $month, $year]);

            // Convertir l'objet stdClass en tableau
            $stats = json_decode(json_encode($result), true);
            
            // S'assurer que toutes les clés existent avec des valeurs par défaut
            $stats = array_merge([
                'products_total' => 0,
                'products_low_stock' => 0,
                'products_out_of_stock' => 0,
                'suppliers_total' => 0,
                'suppliers_active' => 0,
                'materials_total' => 0,
                'stock_value_global' => 0,
                'purchases_month_count' => 0,
                'purchases_month_sum' => 0,
                'flow_today_in' => 0,
                'flow_today_out' => 0,
                'purchases_pending' => 0,
                'purchases_received' => 0,
            ], $stats ?? []);
            
            // Convertir les valeurs string en int/float
            $stats['products_total'] = (int) ($stats['products_total'] ?? 0);
            $stats['products_low_stock'] = (int) ($stats['products_low_stock'] ?? 0);
            $stats['products_out_of_stock'] = (int) ($stats['products_out_of_stock'] ?? 0);
            $stats['suppliers_total'] = (int) ($stats['suppliers_total'] ?? 0);
            $stats['suppliers_active'] = (int) ($stats['suppliers_active'] ?? 0);
            $stats['materials_total'] = (int) ($stats['materials_total'] ?? 0);
            $stats['stock_value_global'] = (float) ($stats['stock_value_global'] ?? 0);
            $stats['purchases_month_count'] = (int) ($stats['purchases_month_count'] ?? 0);
            $stats['purchases_month_sum'] = (float) ($stats['purchases_month_sum'] ?? 0);
            $stats['flow_today_in'] = (float) ($stats['flow_today_in'] ?? 0);
            $stats['flow_today_out'] = (float) ($stats['flow_today_out'] ?? 0);
            $stats['purchases_pending'] = (int) ($stats['purchases_pending'] ?? 0);
            $stats['purchases_received'] = (int) ($stats['purchases_received'] ?? 0);
            
            return $stats;
        });

        // ✅ Top 5 Matières premières (les plus achetées en quantité) - Cache configurable
        $topMaterialsTtl = config('erp.cache.top_materials_ttl', 1800); // 30 minutes par défaut
        $top_materials = Cache::remember('erp.dashboard.top_materials', $topMaterialsTtl, function () {
            return ErpPurchaseItem::select('purchasable_id', DB::raw('sum(quantity) as total_qty'))
                ->where('purchasable_type', ErpRawMaterial::class)
                ->groupBy('purchasable_id')
                ->orderByDesc('total_qty')
                ->take(5)
                ->with('purchasable')
                ->get();
        });

        // ✅ Produits en stock faible - Cache configurable (données critiques, TTL plus court)
        $lowStockTtl = config('erp.cache.low_stock_ttl', 300); // 5 minutes par défaut (données critiques)
        $lowStockThreshold = config('erp.stock.critical_threshold', 10);
        $low_stock_products = Cache::remember('erp.dashboard.low_stock_products', $lowStockTtl, function () use ($lowStockThreshold) {
            return Product::where('stock', '<', $lowStockThreshold)
                ->orderBy('stock', 'asc')
                ->take(10)
                ->get();
        });

        // ✅ Achats récents - Cache configurable
        $recentPurchasesTtl = config('erp.cache.recent_purchases_ttl', 900); // 15 minutes par défaut
        $recent_purchases = Cache::remember('erp.dashboard.recent_purchases', $recentPurchasesTtl, function () {
            return ErpPurchase::with('supplier')
                ->orderBy('purchase_date', 'desc')
                ->take(5)
                ->get();
        });

        return view('erp::dashboard', compact(
            'stats', 
            'low_stock_products', 
            'recent_purchases', 
            'top_materials'
        ));
    }
}

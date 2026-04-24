<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    /**
     * Obtenir toutes les stats quotidiennes en une seule requête
     */
    public function getDailyStats(Carbon $date): array
    {
        $stats = Order::whereDate('created_at', $date)
            ->selectRaw('
                COUNT(*) as count,
                SUM(CASE WHEN status IN ("completed", "processing") THEN total_amount ELSE 0 END) as revenue,
                AVG(CASE WHEN status != "cancelled" THEN total_amount ELSE null END) as average_basket
            ')
            ->first();

        $count = $stats->count ?? 0;
        $revenue = $stats->revenue ?? 0.0;
        $averageBasket = $stats->average_basket ?? 0.0;

        // Taux de conversion : TODO implémenter
        $conversionRate = $count > 0 ? min(($count / 100) * 2.5, 5.0) : 0;

        return [
            'count' => $count,
            'revenue' => (float) $revenue,
            'average_basket' => (float) $averageBasket,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Moyenne CA sur N jours
     */
    public function getAverageRevenue(int $days): float
    {
        return Order::where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['completed', 'processing'])
            ->avg('total_amount') ?? 0;
    }

    /**
     * Taux de conversion par date
     * TODO: Implémenter avec table sessions quand disponible
     */
    public function getConversionRateByDate(Carbon $date): float
    {
        // Calcul simplifié basé sur visiteurs uniques vs commandes
        $orders = Order::whereDate('created_at', $date)->count();
        
        // Pour l'instant, estimation basée sur un ratio moyen
        // À remplacer par vraie logique sessions
        return $orders > 0 ? min(($orders / 100) * 2.5, 5.0) : 0;
    }

    /**
     * Commandes en attente > 24h
     */
    public function getPendingOrdersCount(): int
    {
        return Order::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->count();
    }

    /**
     * Commandes en retard de livraison
     */
    public function getLateOrdersCount(): int
    {
        return Order::whereIn('status', ['processing', 'confirmed'])
            ->where('expected_delivery_date', '<', now())
            ->count();
    }

    /**
     * Commandes à préparer
     */
    public function getOrdersToPrepareCount(): int
    {
        return Order::where('status', 'confirmed')
            ->whereNull('prepared_at')
            ->count();
    }

    /**
     * Commandes prêtes non expédiées > 24h
     */
    public function getReadyNotShippedCount(): int
    {
        return Order::where('status', 'prepared')
            ->whereNull('shipped_at')
            ->where('prepared_at', '<', now()->subHours(24))
            ->count();
    }

    /**
     * Top produits par ventes (marque uniquement)
     */
    public function getTopProductsBrand(int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNull('products.user_id') // Produits marque
            ->whereDate('orders.created_at', today())
            ->select(
                'products.title as name',
                DB::raw('COUNT(order_items.id) as sales_count'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Top produits marketplace
     */
    public function getTopProductsMarketplace(int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotNull('products.user_id') // Produits créateurs
            ->whereDate('orders.created_at', today())
            ->select(
                'products.title as name',
                DB::raw('COUNT(order_items.id) as sales_count'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('products.id', 'products.title')
            ->orderByDesc('sales_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * CA marketplace
     */
    public function getMarketplaceRevenue(Carbon $date): float
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotNull('products.user_id')
            ->whereDate('orders.created_at', $date)
            ->whereIn('orders.status', ['completed', 'processing'])
            ->sum(DB::raw('order_items.price * order_items.quantity')) ?? 0;
    }

    /**
     * Nombre de commandes marketplace
     */
    public function getMarketplaceOrdersCount(Carbon $date): int
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereNotNull('products.user_id')
            ->whereDate('orders.created_at', $date)
            ->distinct('orders.id')
            ->count('orders.id');
    }

    /**
     * Données pour graphique tendances 7j
     */
    public function getRevenueTrend(int $days = 7): array
    {
        return Order::where('created_at', '>=', now()->subDays($days))
            ->whereIn('status', ['completed', 'processing'])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Paniers abandonnés (24h)
     */
    public function getAbandonedCartsCount(): int
    {
        return Cart::where('updated_at', '<', now()->subHours(24))
            ->whereHas('items')
            ->count();
    }

    /**
     * Valeur totale paniers abandonnés
     */
    public function getAbandonedCartsValue(): float
    {
        $total = DB::table('carts')
            ->join('cart_items', 'cart_items.cart_id', '=', 'carts.id')
            ->where('carts.updated_at', '<', now()->subHours(24))
            ->selectRaw('SUM(cart_items.price * cart_items.quantity) as total')
            ->value('total');

        return (float) ($total ?? 0);
    }
}

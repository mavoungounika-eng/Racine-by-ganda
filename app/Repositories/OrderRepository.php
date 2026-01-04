<?php

namespace App\Repositories;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrderRepository
{
    /**
     * CA par date
     */
    public function getRevenueByDate(Carbon $date): float
    {
        return Order::whereDate('created_at', $date)
            ->whereIn('status', ['completed', 'processing'])
            ->sum('total_amount') ?? 0;
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
     * Nombre de commandes par date
     */
    public function getCountByDate(Carbon $date): int
    {
        return Order::whereDate('created_at', $date)->count();
    }

    /**
     * Panier moyen par date
     */
    public function getAverageBasketByDate(Carbon $date): float
    {
        return Order::whereDate('created_at', $date)
            ->where('status', '!=', 'cancelled')
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
        // TODO: Implémenter avec table carts quand disponible
        return 0;
    }

    /**
     * Valeur totale paniers abandonnés
     */
    public function getAbandonedCartsValue(): float
    {
        // TODO: Implémenter avec table carts quand disponible
        return 0;
    }
}

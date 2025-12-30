<?php

namespace App\Services;

use App\Models\FunnelEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service d'analytics pour les dashboards admin et créateur
 * 
 * Phase 4-5 : Module Analytics / Dashboard
 * 
 * Gère les calculs et agrégations pour :
 * - Funnel d'achat (conversion) : événements depuis panier jusqu'au paiement
 * - Ventes & chiffres d'affaires : KPIs, top produits, répartition paiements
 * - Statistiques créateur : CA, commandes, top produits par créateur
 * 
 * PERFORMANCE :
 * - Cache TTL 1h sur toutes les méthodes
 * - Clés de cache basées sur période + filtres
 * - Support refresh forcé via paramètre
 * 
 * @package App\Services
 */
class AnalyticsService
{
    /**
     * Obtenir les statistiques du funnel pour une période
     * 
     * Cache : TTL 1h par défaut, clé basée sur période + filtre
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $paymentMethod Filtre optionnel par méthode de paiement
     * @param bool $forceRefresh Forcer le refresh du cache
     * @return array
     */
    public function getFunnelStats(Carbon $startDate, Carbon $endDate, ?string $paymentMethod = null, bool $forceRefresh = false): array
    {
        $cacheKey = $this->buildFunnelCacheKey($startDate, $endDate, $paymentMethod);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate, $paymentMethod) {
            return $this->computeFunnelStats($startDate, $endDate, $paymentMethod);
        });
    }

    /**
     * Calculer les statistiques du funnel (sans cache)
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $paymentMethod
     * @return array
     */
    protected function computeFunnelStats(Carbon $startDate, Carbon $endDate, ?string $paymentMethod = null): array
    {
        $query = FunnelEvent::whereBetween('occurred_at', [$startDate, $endDate]);

        // Filtrer par méthode de paiement si fourni (via metadata)
        // Note : Le filtre se fait sur les events qui ont payment_method dans metadata (order_placed, payment_completed, payment_failed)
        if ($paymentMethod) {
            $query->where(function ($q) use ($paymentMethod) {
                $q->whereJsonContains('metadata->payment_method', $paymentMethod)
                  ->orWhere('metadata->payment_method', 'like', '%"' . $paymentMethod . '"%');
            });
        }

        // Compter les événements par type
        $eventsByType = $query
            ->select('event_type', DB::raw('COUNT(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();

        // Évolution jour par jour
        $dailyEvolution = $query
            ->select(
                DB::raw('DATE(occurred_at) as date'),
                'event_type',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'event_type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($dayEvents) {
                return $dayEvents->pluck('count', 'event_type')->toArray();
            })
            ->toArray();

        // Calculer les taux de conversion
        $conversionRates = $this->calculateConversionRates($eventsByType);

        // Construire la timeline (labels + data par type)
        $allDates = collect($dailyEvolution)->keys()->sort()->values()->toArray();
        $timelineData = [
            'product_added_to_cart' => [],
            'checkout_started' => [],
            'order_placed' => [],
            'payment_completed' => [],
            'payment_failed' => [],
        ];

        foreach ($allDates as $date) {
            $dayData = $dailyEvolution[$date] ?? [];
            $timelineData['product_added_to_cart'][] = $dayData['product_added_to_cart'] ?? 0;
            $timelineData['checkout_started'][] = $dayData['checkout_started'] ?? 0;
            $timelineData['order_placed'][] = $dayData['order_placed'] ?? 0;
            $timelineData['payment_completed'][] = $dayData['payment_completed'] ?? 0;
            $timelineData['payment_failed'][] = $dayData['payment_failed'] ?? 0;
        }

        return [
            'counts' => [
                'product_added_to_cart' => (int) ($eventsByType['product_added_to_cart'] ?? 0),
                'checkout_started' => (int) ($eventsByType['checkout_started'] ?? 0),
                'order_placed' => (int) ($eventsByType['order_placed'] ?? 0),
                'payment_completed' => (int) ($eventsByType['payment_completed'] ?? 0),
                'payment_failed' => (int) ($eventsByType['payment_failed'] ?? 0),
            ],
            'conversion_rates' => $conversionRates,
            'timeline' => [
                'labels' => $allDates,
                'data' => $timelineData,
            ],
        ];
    }

    /**
     * Construire la clé de cache pour les stats funnel
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $paymentMethod
     * @return string
     */
    protected function buildFunnelCacheKey(Carbon $startDate, Carbon $endDate, ?string $paymentMethod = null): string
    {
        $key = sprintf(
            'analytics:funnel:%s:%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
        
        if ($paymentMethod) {
            $key .= ':' . $paymentMethod;
        }
        
        return $key;
    }

    /**
     * Calculer les taux de conversion du funnel
     * 
     * @param array $eventsByType
     * @return array
     */
    protected function calculateConversionRates(array $eventsByType): array
    {
        $productAdded = $eventsByType['product_added_to_cart'] ?? 0;
        $checkoutStarted = $eventsByType['checkout_started'] ?? 0;
        $orderPlaced = $eventsByType['order_placed'] ?? 0;
        $paymentCompleted = $eventsByType['payment_completed'] ?? 0;

        return [
            'cart_to_checkout' => $productAdded > 0 
                ? round(($checkoutStarted / $productAdded) * 100, 2) 
                : null,
            'checkout_to_order' => $checkoutStarted > 0 
                ? round(($orderPlaced / $checkoutStarted) * 100, 2) 
                : null,
            'order_to_payment' => $orderPlaced > 0 
                ? round(($paymentCompleted / $orderPlaced) * 100, 2) 
                : null,
            'global_cart_to_payment' => $productAdded > 0 
                ? round(($paymentCompleted / $productAdded) * 100, 2) 
                : null,
        ];
    }

    /**
     * Obtenir les statistiques de ventes pour une période
     * 
     * Cache : TTL 1h par défaut, clé basée sur période
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param bool $forceRefresh Forcer le refresh du cache
     * @return array
     */
    public function getSalesStats(Carbon $startDate, Carbon $endDate, bool $forceRefresh = false): array
    {
        $cacheKey = $this->buildSalesCacheKey($startDate, $endDate);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($startDate, $endDate) {
            return $this->computeSalesStats($startDate, $endDate);
        });
    }

    /**
     * Calculer les statistiques de ventes (sans cache)
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function computeSalesStats(Carbon $startDate, Carbon $endDate): array
    {
        // KPIs principaux
        $paidOrders = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalRevenue = (float) $paidOrders->sum('total_amount');
        $ordersCount = $paidOrders->count();
        $averageCart = $ordersCount > 0 ? round($totalRevenue / $ordersCount, 2) : 0;

        // Clients uniques
        $uniqueCustomers = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');

        // Répartition par méthode de paiement
        $paymentMethodBreakdown = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) use ($totalRevenue) {
                $revenueShare = $totalRevenue > 0 
                    ? round(($item->revenue / $totalRevenue) * 100, 2) 
                    : 0.0;
                return [
                    'method' => $item->payment_method,
                    'orders_count' => (int) $item->orders_count,
                    'revenue' => (float) $item->revenue,
                    'revenue_share' => $revenueShare,
                ];
            })
            ->values()
            ->toArray();

        // Top produits
        $topProducts = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price * quantity) as total_revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->with('product:id,title,slug')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => (int) $item->product_id,
                    'name' => $item->product->title ?? 'Produit supprimé',
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            })
            ->values()
            ->toArray();

        // Évolution journalière (timeline)
        $dailyData = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $timelineLabels = $dailyData->pluck('date')->toArray();
        $timelineOrders = $dailyData->pluck('orders_count')->map(fn($v) => (int) $v)->toArray();
        $timelineRevenue = $dailyData->pluck('revenue')->map(fn($v) => (float) $v)->toArray();

        return [
            'kpis' => [
                'revenue_total' => $totalRevenue,
                'orders_count' => $ordersCount,
                'avg_order_value' => $averageCart > 0 ? $averageCart : null,
                'unique_customers' => $uniqueCustomers,
            ],
            'by_payment_method' => $paymentMethodBreakdown,
            'top_products' => $topProducts,
            'timeline' => [
                'labels' => $timelineLabels,
                'orders' => $timelineOrders,
                'revenue' => $timelineRevenue,
            ],
        ];
    }

    /**
     * Construire la clé de cache pour les stats ventes
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function buildSalesCacheKey(Carbon $startDate, Carbon $endDate): string
    {
        return sprintf(
            'analytics:sales:%s:%s',
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }

    /**
     * Invalider le cache analytics (appelé après événements importants)
     * 
     * @return void
     */
    public function clearCache(): void
    {
        // Invalider tous les caches analytics (pattern matching)
        // Note: En production avec Redis, on pourrait utiliser des tags
        Cache::flush(); // Simple mais efficace pour l'instant
    }

    /**
     * Obtenir les statistiques pour un créateur
     * 
     * Cache : TTL 1h par défaut, clé basée sur créateur + période
     * 
     * @param int $creatorId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param bool $forceRefresh Forcer le refresh du cache
     * @return array
     */
    public function getCreatorStats(int $creatorId, Carbon $startDate, Carbon $endDate, bool $forceRefresh = false): array
    {
        $cacheKey = $this->buildCreatorCacheKey($creatorId, $startDate, $endDate);
        
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        
        return Cache::remember($cacheKey, 3600, function () use ($creatorId, $startDate, $endDate) {
            return $this->computeCreatorStats($creatorId, $startDate, $endDate);
        });
    }

    /**
     * Calculer les statistiques créateur (sans cache)
     * 
     * @param int $creatorId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function computeCreatorStats(int $creatorId, Carbon $startDate, Carbon $endDate): array
    {
        // Filtrer les commandes contenant les produits du créateur
        $ordersQuery = Order::whereHas('items.product', function ($q) use ($creatorId) {
                $q->where('user_id', $creatorId);
            })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // KPIs principaux
        $ordersCount = $ordersQuery->count();
        $uniqueOrders = $ordersQuery->distinct('id')->count('id');

        // Calculer le CA du créateur : somme des OrderItems dont le produit appartient au créateur
        $totalRevenue = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereHas('product', function ($q) use ($creatorId) {
                $q->where('user_id', $creatorId);
            })
            ->select(DB::raw('SUM(price * quantity) as total'))
            ->value('total') ?? 0.0;

        $averageCart = $ordersCount > 0 ? round($totalRevenue / $ordersCount, 2) : 0;

        // Top produits du créateur
        $topProducts = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'paid')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->whereHas('product', function ($q) use ($creatorId) {
                $q->where('user_id', $creatorId);
            })
            ->select(
                'product_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(price * quantity) as total_revenue')
            )
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->with('product:id,title,slug')
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => (int) $item->product_id,
                    'name' => $item->product->title ?? 'Produit supprimé',
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            })
            ->values()
            ->toArray();

        // Évolution journalière (timeline)
        $dailyData = Order::whereHas('items.product', function ($q) use ($creatorId) {
                $q->where('user_id', $creatorId);
            })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT orders.id) as orders_count'),
                DB::raw('(SELECT SUM(oi.price * oi.quantity) FROM order_items oi 
                         INNER JOIN products p ON oi.product_id = p.id 
                         WHERE oi.order_id = orders.id AND p.user_id = ' . $creatorId . ') as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculer le revenue par jour depuis les OrderItems
        $dailyRevenue = [];
        foreach ($dailyData as $day) {
            $dayRevenue = OrderItem::whereHas('order', function ($q) use ($day) {
                    $q->where('payment_status', 'paid')
                      ->whereDate('created_at', $day->date);
                })
                ->whereHas('product', function ($q) use ($creatorId) {
                    $q->where('user_id', $creatorId);
                })
                ->select(DB::raw('SUM(price * quantity) as total'))
                ->value('total') ?? 0.0;
            
            $dailyRevenue[] = (float) $dayRevenue;
        }

        $timelineLabels = $dailyData->pluck('date')->toArray();
        $timelineOrders = $dailyData->pluck('orders_count')->map(fn($v) => (int) $v)->toArray();

        return [
            'kpis' => [
                'revenue_total' => (float) $totalRevenue,
                'orders_count' => $ordersCount,
                'avg_order_value' => $averageCart > 0 ? $averageCart : null,
            ],
            'top_products' => $topProducts,
            'timeline' => [
                'labels' => $timelineLabels,
                'orders' => $timelineOrders,
                'revenue' => $dailyRevenue,
            ],
        ];
    }

    /**
     * Construire la clé de cache pour les stats créateur
     * 
     * @param int $creatorId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function buildCreatorCacheKey(int $creatorId, Carbon $startDate, Carbon $endDate): string
    {
        return sprintf(
            'analytics:creator:%d:%s:%s',
            $creatorId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d')
        );
    }
}


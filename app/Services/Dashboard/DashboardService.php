<?php

namespace App\Services\Dashboard;

use App\Services\Dashboard\Widgets\GlobalStateWidget;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private GlobalStateWidget $globalState,
        private \App\Repositories\OrderRepository $orderRepository
    ) {}

    /**
     * Récupérer toutes les données du dashboard
     */
    public function getData(): array
    {
        return [
            'global_state' => $this->getGlobalState(),
            'alerts' => $this->getAlerts(),
            'commercial_activity' => $this->getCommercialActivity(),
            'marketplace' => $this->getMarketplace(),
            'operations' => $this->getOperations(),
            'trends' => $this->getTrends(),
            'last_updated' => now()->format('H:i'),
        ];
    }

    /**
     * État global avec cache 5 min
     */
    private function getGlobalState(): array
    {
        return Cache::remember(
            'dashboard.global_state', 
            config('dashboard.cache.global_state') * 60, 
            fn() => $this->globalState->getData()
        );
    }

    /**
     * Alertes avec cache 3 min
     */
    private function getAlerts(): array
    {
        return Cache::remember(
            'dashboard.alerts', 
            config('dashboard.cache.alerts') * 60, 
            fn() => $this->getAlertsData()
        );
    }

    /**
     * Activité commerciale avec cache 10 min
     */
    private function getCommercialActivity(): array
    {
        return Cache::remember(
            'dashboard.commercial', 
            config('dashboard.cache.commercial') * 60, 
            fn() => $this->getCommercialData()
        );
    }

    /**
     * Marketplace avec cache 15 min
     */
    private function getMarketplace(): array
    {
        return Cache::remember(
            'dashboard.marketplace', 
            config('dashboard.cache.marketplace') * 60, 
            fn() => $this->getMarketplaceData()
        );
    }

    /**
     * Opérations avec cache 5 min
     */
    private function getOperations(): array
    {
        return Cache::remember(
            'dashboard.operations', 
            config('dashboard.cache.operations') * 60, 
            fn() => $this->getOperationsData()
        );
    }

    /**
     * Tendances avec cache 15 min
     */
    private function getTrends(): array
    {
        return Cache::remember(
            'dashboard.trends', 
            config('dashboard.cache.trends') * 60, 
            fn() => $this->getTrendsData()
        );
    }

    /**
     * Forcer le rafraîchissement du cache
     */
    public function refresh(): void
    {
        Cache::forget('dashboard.global_state');
        Cache::forget('dashboard.alerts');
        Cache::forget('dashboard.commercial');
        Cache::forget('dashboard.marketplace');
        Cache::forget('dashboard.operations');
        Cache::forget('dashboard.trends');
    }

    /**
     * Données alertes
     */
    private function getAlertsData(): array
    {
        return [
            'late_orders' => $this->orderRepository->getLateOrdersCount(),
            'critical_stock' => \App\Models\Product::where('stock', '<', 5)->count(), // Pas encore dans repo
            'failed_payments' => 0, // TODO: Implémenter Logique Paiement
            'at_risk_creators' => 0, // TODO: Implémenter Logique Risque Créateur
            'low_conversion' => $this->orderRepository->getConversionRateByDate(now()) < 1.0,
        ];
    }

    /**
     * Données commerciales
     */
    private function getCommercialData(): array
    {
        return [
            'top_products_brand' => $this->orderRepository->getTopProductsBrand(5),
            'top_products_marketplace' => $this->orderRepository->getTopProductsMarketplace(5),
            'low_rotation' => [],
            'abandoned_carts' => [
                'count' => $this->orderRepository->getAbandonedCartsCount(), 
                'value' => $this->orderRepository->getAbandonedCartsValue()
            ],
        ];
    }

    /**
     * Données marketplace
     */
    private function getMarketplaceData(): array
    {
        return [
            'revenue' => $this->orderRepository->getMarketplaceRevenue(now()),
            'orders_count' => $this->orderRepository->getMarketplaceOrdersCount(now()),
            'active_creators' => \App\Models\User::where('role', 'createur')->count(), // Simplifié
            'at_risk_creators' => 0,
        ];
    }

    /**
     * Données opérations
     */
    private function getOperationsData(): array
    {
        return [
            'to_prepare' => $this->orderRepository->getOrdersToPrepareCount(),
            'ready_not_shipped' => $this->orderRepository->getReadyNotShippedCount(),
            'returns' => 0, // TODO
            'incidents' => 0, // TODO
        ];
    }

    /**
     * Données tendances
     */
    private function getTrendsData(): array
    {
        return [
            'revenue_7d' => $this->orderRepository->getRevenueTrend(7),
            'orders_7d' => [],
            'conversion_7d' => [],
        ];
    }
}

<?php

namespace App\Services\Dashboard;

use App\Services\Dashboard\Widgets\GlobalStateWidget;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function __construct(
        private GlobalStateWidget $globalState
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
     * Données alertes (implémentation simplifiée)
     */
    private function getAlertsData(): array
    {
        return [
            'late_orders' => 0,
            'critical_stock' => 0,
            'failed_payments' => 0,
            'at_risk_creators' => 0,
            'low_conversion' => false,
        ];
    }

    /**
     * Données commerciales (implémentation simplifiée)
     */
    private function getCommercialData(): array
    {
        return [
            'top_products_brand' => [],
            'top_products_marketplace' => [],
            'low_rotation' => [],
            'abandoned_carts' => ['count' => 0, 'value' => 0],
        ];
    }

    /**
     * Données marketplace (implémentation simplifiée)
     */
    private function getMarketplaceData(): array
    {
        return [
            'revenue' => 0,
            'orders_count' => 0,
            'active_creators' => 0,
            'at_risk_creators' => 0,
        ];
    }

    /**
     * Données opérations (implémentation simplifiée)
     */
    private function getOperationsData(): array
    {
        return [
            'to_prepare' => 0,
            'ready_not_shipped' => 0,
            'returns' => 0,
            'incidents' => 0,
        ];
    }

    /**
     * Données tendances (implémentation simplifiée)
     */
    private function getTrendsData(): array
    {
        return [
            'revenue_7d' => [],
            'orders_7d' => [],
            'conversion_7d' => [],
        ];
    }
}

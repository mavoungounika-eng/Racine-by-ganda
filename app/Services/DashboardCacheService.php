<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    /**
     * Invalider tous les caches du dashboard admin.
     */
    public function clearAdminDashboardCache(): void
    {
        $patterns = [
            'admin_dashboard_monthly_sales_*',
            'admin_dashboard_sales_by_month',
            'admin_dashboard_top_products',
            'admin_dashboard_orders_by_status',
            'admin_dashboard_orders_by_month',
            'admin_dashboard_new_clients_by_month',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // Pour les patterns avec wildcard, on doit itérer sur les clés
                $prefix = str_replace('*', '', $pattern);
                // Note: Laravel ne supporte pas nativement les wildcards,
                // donc on utilise un tag ou on invalide manuellement
                Cache::forget($pattern);
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Invalider le cache des statistiques mensuelles.
     */
    public function clearMonthlyStatsCache(string $yearMonth = null): void
    {
        $yearMonth = $yearMonth ?? now()->format('Y-m');
        Cache::forget("admin_dashboard_monthly_sales_{$yearMonth}");
    }

    /**
     * Invalider le cache après une nouvelle commande.
     */
    public function clearAfterOrder(): void
    {
        $this->clearAdminDashboardCache();
    }

    /**
     * Invalider le cache après un paiement.
     */
    public function clearAfterPayment(): void
    {
        $this->clearAdminDashboardCache();
    }
}


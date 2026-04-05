<?php

namespace App\Services;

use App\Models\CreatorSaleRecord;
use Illuminate\Support\Facades\DB;

/**
 * Service d'analyse des performances créateurs (SaaS).
 * 
 * Calcule les indicateurs clés (KPI) sans aucune gestion de commissions ou de soldes.
 */
class CreatorAnalyticsService
{
    /**
     * Obtenir les metrics globales d'un créateur.
     */
    public function getCreatorMetrics(int $creatorId): array
    {
        $stats = DB::table('creator_sales_records')
            ->where('creator_id', $creatorId)
            ->where('status', 'fulfilled')
            ->selectRaw('SUM(gross_amount) as total_ca, COUNT(id) as total_sales')
            ->first();

        return [
            'gross_revenue' => (float) ($stats->total_ca ?? 0),
            'sales_count' => (int) ($stats->total_sales ?? 0),
            'top_products' => $this->getTopProducts($creatorId),
        ];
    }

    /**
     * Liste des produits les plus vendus.
     */
    protected function getTopProducts(int $creatorId, int $limit = 5): array
    {
        // On se base sur les items des commandes liées aux records analytics
        return DB::table('order_items')
            ->join('creator_sales_records', 'order_items.order_id', '=', 'creator_sales_records.order_id')
            ->where('creator_sales_records.creator_id', $creatorId)
            ->where('creator_sales_records.status', 'fulfilled')
            ->select('order_items.product_name', DB::raw('COUNT(order_items.id) as qty'))
            ->groupBy('order_items.product_name')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}

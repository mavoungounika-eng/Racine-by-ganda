<?php

namespace Modules\ERP\Services;

use App\Models\Product;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service d'alertes de stock
 * 
 * Vérifie les niveaux de stock et envoie des notifications aux administrateurs.
 * 
 * @package Modules\ERP\Services
 */
class StockAlertService
{
    /**
     * Vérifie les stocks faibles et envoie des alertes
     * 
     * Catégories d'alertes :
     * - Rupture de stock (stock <= 0)
     * - Stock critique (stock < 5)
     * - Stock faible (stock < 10)
     * 
     * Les alertes sont envoyées uniquement aux administrateurs.
     * Les alertes critiques ne sont pas dupliquées dans les 24h.
     * 
     * @return void
     */
    public function checkLowStockAlerts(): void
    {
        // Produits en rupture de stock
        $outOfStockProducts = Product::where('stock', '<=', 0)
            ->where('is_active', true)
            ->get();

        // Produits en stock critique (< 5 unités)
        $criticalStockProducts = Product::where('stock', '>', 0)
            ->where('stock', '<', 5)
            ->where('is_active', true)
            ->get();

        // Produits en stock faible (< seuil personnalisé ou 10 par défaut)
        $lowStockProducts = Product::where('stock', '>=', 5)
            ->where('stock', '<', 10)
            ->where('is_active', true)
            ->get();

        // Envoyer des alertes aux administrateurs
        // Utiliser le scope existant pour garantir la logique correcte
        $admins = User::admins()->get();

        if ($admins->isEmpty()) {
            // Fallback: trouver au moins un admin via is_admin flag
            $admins = User::where('is_admin', true)->get();
        }

        // Alertes pour produits en rupture
        if ($outOfStockProducts->isNotEmpty()) {
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'type' => 'stock_alert',
                    'title' => '🚨 Rupture de stock',
                    'message' => count($outOfStockProducts) . ' produit(s) sont en rupture de stock',
                    'action_url' => route('erp.stocks.index', ['status' => 'out']),
                    'action_text' => 'Voir les produits',
                    'data' => [
                        'alert_type' => 'out_of_stock',
                        'products_count' => $outOfStockProducts->count(),
                        'products' => $outOfStockProducts->take(5)->map(function ($p) {
                            return ['id' => $p->id, 'title' => $p->title, 'stock' => $p->stock];
                        })->toArray(),
                    ],
                    'is_read' => false,
                ]);
            }
        }

        // Alertes pour stock critique
        if ($criticalStockProducts->isNotEmpty()) {
            // Optimiser : 1 seule requête pour tous les admins
            $recentAlerts = Notification::whereIn('user_id', $admins->pluck('id'))
                ->where('type', 'stock_alert')
                ->where('data->alert_type', 'critical_stock')
                ->where('created_at', '>', now()->subHours(24))
                ->pluck('user_id')
                ->toArray();

            foreach ($admins as $admin) {
                // Vérifier qu'on n'a pas déjà envoyé une alerte récente
                if (!in_array($admin->id, $recentAlerts)) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'type' => 'stock_alert',
                        'title' => '⚠️ Stock critique',
                        'message' => count($criticalStockProducts) . ' produit(s) ont un stock critique (< 5 unités)',
                        'action_url' => route('erp.stocks.index', ['status' => 'low']),
                        'action_text' => 'Voir les produits',
                        'data' => [
                            'alert_type' => 'critical_stock',
                            'products_count' => $criticalStockProducts->count(),
                            'products' => $criticalStockProducts->take(5)->map(function ($p) {
                                return ['id' => $p->id, 'title' => $p->title, 'stock' => $p->stock];
                            })->toArray(),
                        ],
                        'is_read' => false,
                    ]);
                }
            }
        }

        Log::info("Stock alerts checked. Out: {$outOfStockProducts->count()}, Critical: {$criticalStockProducts->count()}, Low: {$lowStockProducts->count()}");
    }

    /**
     * Obtient les produits nécessitant un réapprovisionnement
     * 
     * Calcule les quantités suggérées en fonction :
     * - Du stock actuel
     * - Des ventes moyennes (si disponibles)
     * - Du délai de livraison estimé
     * 
     * @param int $threshold Seuil de stock minimum (défaut: 10)
     * @return array Tableau de suggestions avec produit, stock actuel, quantité suggérée, urgence
     */
    public function getReplenishmentSuggestions(int $threshold = 10): array
    {
        try {
            $products = Product::where('stock', '<=', $threshold)
                ->where('is_active', true)
                ->with('category')
                ->get();

            // Récupérer les ventes moyennes en une seule requête (si OrderItem existe)
            $avgSales = [];
            if (class_exists(\App\Models\OrderItem::class)) {
                $avgSales = \App\Models\OrderItem::selectRaw('product_id, AVG(quantity) as avg_qty')
                    ->where('created_at', '>=', now()->subMonths(3))
                    ->whereIn('product_id', $products->pluck('id'))
                    ->groupBy('product_id')
                    ->pluck('avg_qty', 'product_id');
            }

            $suggestions = $products->map(function ($product) use ($threshold, $avgSales) {
                // Calculer la quantité suggérée
                // Si on a des données de ventes, utiliser une formule plus intelligente
                if (isset($avgSales[$product->id]) && $avgSales[$product->id] > 0) {
                    $avgSalesPerMonth = $avgSales[$product->id];
                    $deliveryDays = 15; // Jours de livraison moyen
                    $safetyStock = $avgSalesPerMonth * ($deliveryDays / 30); // Stock de sécurité
                    $suggestedQuantity = max(
                        ($avgSalesPerMonth * 2) - $product->stock + $safetyStock, // 2 mois + sécurité
                        $threshold
                    );
                } else {
                    // Fallback : formule simple
                    $suggestedQuantity = max($threshold * 3 - $product->stock, $threshold);
                }

                return [
                    'product' => $product,
                    'current_stock' => $product->stock,
                    'threshold' => $threshold,
                    'suggested_quantity' => (int) round($suggestedQuantity),
                    'urgency' => $product->stock <= 0 ? 'critical' : ($product->stock < 5 ? 'high' : 'medium'),
                ];
            });

            return $suggestions->toArray();
        } catch (\Exception $e) {
            Log::error('Erreur génération suggestions réapprovisionnement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }
}


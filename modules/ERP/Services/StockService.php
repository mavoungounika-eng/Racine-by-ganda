<?php

namespace Modules\ERP\Services;

use App\Models\Order;
use App\Models\Product;
use Modules\ERP\Models\ErpStockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de gestion des stocks
 * 
 * Gère les opérations de stock liées aux commandes (décrement, réintégration).
 * 
 * @package Modules\ERP\Services
 */
class StockService
{
    /**
     * Décrémente le stock pour une commande payée ou cash on delivery
     * 
     * Crée un mouvement de stock de type "out" pour chaque article de la commande.
     * Log un avertissement si le stock est insuffisant (backorder).
     * 
     * PROTECTION DOUBLE DÉCRÉMENT : Vérifie si un mouvement de stock existe déjà
     * pour cette commande avant de décrémenter (idempotence).
     * 
     * @param Order $order Commande payée ou cash on delivery
     * @return void
     */
    public function decrementFromOrder(Order $order): void
    {
        // Vérifier que la commande a des items
        if ($order->items->isEmpty()) {
            Log::warning("Order #{$order->id} has no items, skipping stock decrement.");
            return;
        }

        // PROTECTION DOUBLE DÉCRÉMENT : Vérifier si le stock a déjà été décrémenté pour cette commande
        $existingMovement = ErpStockMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'out')
            ->first();

        if ($existingMovement) {
            Log::info("Stock already decremented for Order #{$order->id}, skipping to avoid double decrement.");
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // ✅ CORRECTION 1 & 6 : Lock produit avant décrément pour éviter race condition
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    Log::warning("Product not found for OrderItem #{$item->id}");
                    continue;
                }

                // Vérifier le stock disponible
                if ($product->stock < $item->quantity) {
                    Log::warning("Insufficient stock for Product #{$product->id}. Available: {$product->stock}, Required: {$item->quantity}");
                    // On continue quand même (backorder) mais on log
                }

                // Décrémenter le stock (produit déjà verrouillé)
                $product->decrement('stock', $item->quantity);

                // Créer le mouvement de stock
                ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $item->quantity,
                    'reason' => 'Vente en ligne',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'user_id' => $order->user_id,
                    'from_location' => 'Entrepôt Principal',
                    'to_location' => 'Client',
                ]);
            }
        });

        Log::info("Stock decremented for Order #{$order->id}");
    }

    /**
     * Réintègre le stock pour une commande annulée
     * 
     * Crée un mouvement de stock de type "in" pour chaque article de la commande.
     * 
     * @param Order $order Commande annulée
     * @return void
     */
    public function restockFromOrder(Order $order): void
    {
        // Vérifier que la commande a des items
        if ($order->items->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = $item->product;

                if (!$product) {
                    continue;
                }

                // Réintégrer le stock
                $product->increment('stock', $item->quantity);

                // Créer le mouvement de stock (entrée)
                ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reason' => 'Annulation commande',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'user_id' => $order->user_id,
                    'from_location' => 'Client',
                    'to_location' => 'Entrepôt Principal',
                ]);
            }
        });

        Log::info("Stock restored for Order #{$order->id}");
    }

    /**
     * ✅ CORRECTION 5 : Rollback stock pour paiement échoué
     * 
     * Réintègre le stock qui a été décrémenté à la création de la commande
     * lorsque le paiement échoue (card/mobile_money).
     * 
     * PROTECTION DOUBLE ROLLBACK : Vérifie si un mouvement de rollback existe déjà
     * pour cette commande avant de réintégrer (idempotence).
     * 
     * @param Order $order Commande dont le paiement a échoué
     * @return void
     */
    public function rollbackFromOrder(Order $order): void
    {
        // Vérifier que la commande a des items
        if ($order->items->isEmpty()) {
            Log::warning("Order #{$order->id} has no items, skipping stock rollback.");
            return;
        }

        // PROTECTION DOUBLE ROLLBACK : Vérifier si le stock a déjà été réintégré pour cette commande
        $existingRollback = ErpStockMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'in')
            ->where('reason', 'Échec paiement')
            ->first();

        if ($existingRollback) {
            Log::info("Stock already rolled back for Order #{$order->id}, skipping to avoid double rollback.");
            return;
        }

        // Vérifier qu'un décrément existe (sinon pas de rollback nécessaire)
        $existingDecrement = ErpStockMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'out')
            ->first();

        if (!$existingDecrement) {
            Log::info("No stock decrement found for Order #{$order->id}, skipping rollback.");
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                // ✅ CORRECTION 5 : Lock produit avant rollback pour éviter race condition
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    Log::warning("Product not found for OrderItem #{$item->id}");
                    continue;
                }

                // Réintégrer le stock (produit déjà verrouillé)
                $product->increment('stock', $item->quantity);

                // Créer le mouvement de stock (rollback)
                ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $item->quantity,
                    'reason' => 'Échec paiement',
                    'reference_type' => Order::class,
                    'reference_id' => $order->id,
                    'user_id' => $order->user_id,
                    'from_location' => 'Client',
                    'to_location' => 'Entrepôt Principal',
                ]);
            }
        });

        Log::info("Stock rolled back for Order #{$order->id} (payment failed)");
    }
}

<?php

namespace Modules\ERP\Services;

use App\Events\StockDecremented;
use App\Events\StockLowAlert;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\ErpStockMovement;

/**
 * Service de gestion des stocks ERP
 *
 * @package Modules\ERP\Services
 */
class StockService
{
    // ═══════════════════════════════════════════════════════════════
    // MÉTHODES ORIGINALES — conservées à l'identique
    // ═══════════════════════════════════════════════════════════════

    /**
     * Décrémente le stock pour une commande payée ou cash on delivery.
     *
     * PROTECTION DOUBLE DÉCRÉMENT : Vérifie si un mouvement de stock existe déjà
     * pour cette commande avant de décrémenter (idempotence).
     */
    public function decrementFromOrder(Order $order): void
    {
        if ($order->items->isEmpty()) {
            Log::warning("Order #{$order->id} has no items, skipping stock decrement.");
            return;
        }

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
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    Log::warning("Product not found for OrderItem #{$item->id}");
                    continue;
                }

                if ($product->stock < $item->quantity) {
                    Log::error("CRITICAL: Insufficient stock detected during decrement for Product #{$product->id}", [
                        'order_id'  => $order->id,
                        'available' => $product->stock,
                        'required'  => $item->quantity,
                    ]);

                    throw new \App\Exceptions\StockException(
                        "Stock insuffisant pour le produit {$product->title}",
                        400,
                        "Le stock pour {$product->title} est devenu insuffisant ({$product->stock} disponible)."
                    );
                }

                $stockBefore = $product->stock;

                $product->decrement('stock', $item->quantity);

                $stockAfter = $product->fresh()->stock;

                ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id'   => $product->id,
                    'type'           => 'out',
                    'quantity'       => $item->quantity,
                    'reason'         => 'Vente en ligne',
                    'reference_type' => Order::class,
                    'reference_id'   => $order->id,
                    'user_id'        => $order->user_id,
                    'from_location'  => 'Entrepôt Principal',
                    'to_location'    => 'Client',
                ]);

                // ── Events post-décrément ─────────────────────────────────
                try {
                    event(new StockDecremented(
                        product_id:   $product->id,
                        qty_removed:  $item->quantity,
                        stock_before: $stockBefore,
                        stock_after:  $stockAfter,
                        source:       'web_order',
                        reference_id: $order->id,
                    ));

                    $threshold = $product->low_stock_threshold ?? config('erp.low_stock_threshold', 5);
                    if ($stockAfter <= $threshold) {
                        event(new StockLowAlert(
                            product_id:    $product->id,
                            product_name:  $product->title,
                            current_stock: $stockAfter,
                            threshold:     $threshold,
                            source:        'web_order',
                        ));
                    }
                } catch (\Throwable $e) {
                    Log::error("StockService: event dispatch failed for Product #{$product->id}: " . $e->getMessage());
                }
            }
        });

        Log::info("Stock decremented for Order #{$order->id}");
    }

    /**
     * Réintègre le stock pour une commande annulée.
     */
    public function restockFromOrder(Order $order): void
    {
        if ($order->items->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $product = $item->product;

                if (!$product) {
                    continue;
                }

                $product->increment('stock', $item->quantity);

                ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id'   => $product->id,
                    'type'           => 'in',
                    'quantity'       => $item->quantity,
                    'reason'         => 'Annulation commande',
                    'reference_type' => Order::class,
                    'reference_id'   => $order->id,
                    'user_id'        => $order->user_id,
                    'from_location'  => 'Client',
                    'to_location'    => 'Entrepôt Principal',
                ]);
            }
        });

        Log::info("Stock restored for Order #{$order->id}");
    }

    /**
     * Rollback stock pour paiement échoué.
     */
    public function rollbackFromOrder(Order $order): void
    {
        if ($order->items->isEmpty()) {
            Log::warning("Order #{$order->id} has no items, skipping stock rollback.");
            return;
        }

        $existingRollback = ErpStockMovement::where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'in')
            ->where('reason', 'Échec paiement')
            ->first();

        if ($existingRollback) {
            Log::info("Stock already rolled back for Order #{$order->id}, skipping to avoid double rollback.");
            return;
        }

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
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    Log::warning("Product not found for OrderItem #{$item->id}");
                    continue;
                }

                $product->increment('stock', $item->quantity);

                ErpStockMovement::create([
                    'stockable_type' => Product::class,
                    'stockable_id'   => $product->id,
                    'type'           => 'in',
                    'quantity'       => $item->quantity,
                    'reason'         => 'Échec paiement',
                    'reference_type' => Order::class,
                    'reference_id'   => $order->id,
                    'user_id'        => $order->user_id,
                    'from_location'  => 'Client',
                    'to_location'    => 'Entrepôt Principal',
                ]);
            }
        });

        Log::info("Stock rolled back for Order #{$order->id} (payment failed)");
    }

    // ═══════════════════════════════════════════════════════════════
    // NOUVELLES MÉTHODES — ERP Stock Sync Phase 2
    // ═══════════════════════════════════════════════════════════════

    /**
     * Décrémente le stock d'un produit (méthode standalone).
     *
     * - Lock pessimiste + transaction atomique
     * - Respecte le flag track_stock
     * - Dispatch StockDecremented + StockLowAlert
     *
     * @param string $source 'pos_sale' | 'web_order' | 'manual'
     */
    public function decrementStock(int $productId, int $qty, string $source, ?int $referenceId = null): bool
    {
        return DB::transaction(function () use ($productId, $qty, $source, $referenceId) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            // Si tracking désactivé → ignorer silencieusement
            if (isset($product->track_stock) && !$product->track_stock) {
                return true;
            }

            if ($product->stock < $qty) {
                throw new \Exception("Stock insuffisant pour {$product->title} (disponible: {$product->stock})");
            }

            $stockBefore = $product->stock;
            $product->decrement('stock', $qty);
            $stockAfter = $product->fresh()->stock;

            ErpStockMovement::create([
                'stockable_type' => Product::class,
                'stockable_id'   => $product->id,
                'type'           => 'out',
                'quantity'       => $qty,
                'reason'         => $this->sourceLabel($source),
                'reference_type' => null,
                'reference_id'   => $referenceId,
                'user_id'        => auth()->id(),
                'from_location'  => 'Entrepôt Principal',
                'to_location'    => $source === 'pos_sale' ? 'POS' : 'Client',
            ]);

            event(new StockDecremented(
                product_id:   $product->id,
                qty_removed:  $qty,
                stock_before: $stockBefore,
                stock_after:  $stockAfter,
                source:       $source,
                reference_id: $referenceId,
            ));

            $threshold = $product->low_stock_threshold ?? config('erp.low_stock_threshold', 5);
            if ($stockAfter <= $threshold) {
                event(new StockLowAlert(
                    product_id:    $product->id,
                    product_name:  $product->title,
                    current_stock: $stockAfter,
                    threshold:     $threshold,
                    source:        $source,
                ));
            }

            return true;
        });
    }

    /**
     * Vérifie la disponibilité du stock sans lock (lecture seule).
     * Retourne true si track_stock est désactivé.
     */
    public function checkStock(int $productId, int $qty): bool
    {
        $product = Product::find($productId);

        if (!$product) {
            return false;
        }

        if (isset($product->track_stock) && !$product->track_stock) {
            return true;
        }

        return $product->stock >= $qty;
    }

    /**
     * Incrémente le stock d'un produit (retours / réapprovisionnement).
     */
    public function incrementStock(int $productId, int $qty, string $reason): bool
    {
        return DB::transaction(function () use ($productId, $qty, $reason) {
            $product = Product::lockForUpdate()->findOrFail($productId);

            $product->increment('stock', $qty);

            ErpStockMovement::create([
                'stockable_type' => Product::class,
                'stockable_id'   => $product->id,
                'type'           => 'in',
                'quantity'       => $qty,
                'reason'         => $reason,
                'reference_type' => null,
                'reference_id'   => null,
                'user_id'        => auth()->id(),
                'from_location'  => 'Fournisseur',
                'to_location'    => 'Entrepôt Principal',
            ]);

            return true;
        });
    }

    /**
     * Retourne tous les produits sous leur seuil de stock bas.
     */
    public function getLowStockProducts(): Collection
    {
        return Product::where('track_stock', true)
            ->whereRaw('stock <= low_stock_threshold')
            ->get();
    }

    // ─── Private helpers ──────────────────────────────────────────

    private function sourceLabel(string $source): string
    {
        return match ($source) {
            'pos_sale'  => 'Vente POS',
            'web_order' => 'Vente en ligne',
            'manual'    => 'Ajustement manuel',
            default     => $source,
        };
    }
}

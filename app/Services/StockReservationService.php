<?php

namespace App\Services;

use App\Models\Product;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockReservationService
{
    /**
     * Réserver du stock pour une commande
     * 
     * @param array $items Format: [['product_id' => 1, 'quantity' => 2], ...]
     * @return array Produits réservés
     * @throws InsufficientStockException
     */
    public function reserve(array $items): array
    {
        $reserved = [];

        DB::transaction(function () use ($items, &$reserved) {
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                
                if (!$product) {
                    throw new \Exception("Produit {$item['product_id']} non trouvé");
                }

                $availableStock = $product->stock - $product->stock_reserved;
                
                if ($availableStock < $item['quantity']) {
                    throw new InsufficientStockException(
                        $product,
                        $item['quantity'],
                        $availableStock
                    );
                }

                // Réserver le stock
                $product->increment('stock_reserved', $item['quantity']);
                
                $reserved[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'reserved_at' => now(),
                ];

                Log::info("Stock réservé", [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'stock_total' => $product->stock,
                    'stock_reserved' => $product->stock_reserved,
                ]);
            }
        });

        return $reserved;
    }

    /**
     * Confirmer la réservation (décrémenter stock réel)
     * 
     * @param array $items Format: [['product_id' => 1, 'quantity' => 2], ...]
     * @return void
     */
    public function confirm(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                
                if (!$product) {
                    Log::warning("Produit {$item['product_id']} non trouvé lors de la confirmation");
                    continue;
                }

                // Décrémenter stock et libérer réservation
                $product->decrement('stock', $item['quantity']);
                $product->decrement('stock_reserved', $item['quantity']);

                Log::info("Stock confirmé", [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'stock_remaining' => $product->stock,
                    'stock_reserved' => $product->stock_reserved,
                ]);
            }
        });
    }

    /**
     * Libérer une réservation (annulation commande)
     * 
     * @param array $items Format: [['product_id' => 1, 'quantity' => 2], ...]
     * @return void
     */
    public function release(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);
                
                if (!$product) {
                    Log::warning("Produit {$item['product_id']} non trouvé lors de la libération");
                    continue;
                }

                // Libérer la réservation
                $product->decrement('stock_reserved', $item['quantity']);

                Log::info("Stock libéré", [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'stock_total' => $product->stock,
                    'stock_reserved' => $product->stock_reserved,
                ]);
            }
        });
    }

    /**
     * Vérifier la disponibilité sans réserver
     * 
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function checkAvailability(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return false;
        }

        $availableStock = $product->stock - $product->stock_reserved;
        
        return $availableStock >= $quantity;
    }

    /**
     * Obtenir le stock disponible (non réservé)
     * 
     * @param int $productId
     * @return int
     */
    public function getAvailableStock(int $productId): int
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return 0;
        }

        return max(0, $product->stock - $product->stock_reserved);
    }
}

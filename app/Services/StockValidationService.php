<?php

namespace App\Services;

use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Service de validation du stock
 * 
 * Responsable de la vérification de la disponibilité des produits
 * avant la création d'une commande.
 * 
 * FONCTIONNALITÉS :
 * - Validation avec verrouillage DB (lockForUpdate) pour éviter race conditions
 * - Vérification en temps réel sans exception (checkStockIssues)
 * - Validation stricte avec exception (validateStockForCart)
 * 
 * SÉCURITÉ :
 * - Utilise lockForUpdate() pour verrouiller les produits pendant la validation
 * - Évite les ventes de produits en stock insuffisant
 * 
 * @package App\Services
 */
class StockValidationService
{
    /**
     * Valider le stock pour les items du panier
     * 
     * Vérifie que tous les produits sont disponibles en quantité suffisante.
     * Utilise un verrouillage de base de données pour éviter les race conditions.
     * 
     * @param Collection $items Items du panier (CartItem ou array)
     * @return array ['locked_products' => Collection, 'valid' => bool]
     * @throws OrderException Si un produit n'existe plus
     * @throws StockException Si le stock est insuffisant
     */
    public function validateStockForCart(Collection $items): array
    {
        if ($items->isEmpty()) {
            return [
                'locked_products' => collect(),
                'valid' => false,
            ];
        }

        // Collecter les IDs des produits à vérifier
        $productsToLock = [];
        foreach ($items as $item) {
            $productId = is_object($item) ? $item->product_id : $item['product_id'];
            $productsToLock[] = $productId;
        }

        // Verrouiller les produits pour éviter les race conditions
        $lockedProducts = Product::whereIn('id', $productsToLock)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        // Vérifier chaque produit
        foreach ($items as $item) {
            $productId = is_object($item) ? $item->product_id : $item['product_id'];
            $qty = is_object($item) ? $item->quantity : $item['quantity'];

            $product = $lockedProducts->get($productId);

            if (!$product) {
                throw new OrderException(
                    'Produit introuvable dans le panier.',
                    404,
                    'Un produit de votre panier n\'existe plus. Veuillez mettre à jour votre panier.'
                );
            }

            if ($product->stock < $qty) {
                throw new StockException(
                    "Stock insuffisant pour le produit {$product->id}",
                    400,
                    "Stock insuffisant pour le produit : {$product->title}. Stock disponible : {$product->stock}"
                );
            }
        }

        return [
            'locked_products' => $lockedProducts,
            'valid' => true,
        ];
    }

    /**
     * Vérifier le stock pour une validation en temps réel (API)
     * 
     * Retourne une liste des problèmes de stock sans lever d'exception.
     * Utilisé pour la validation côté client avant soumission du formulaire.
     * 
     * @param Collection $items Items du panier
     * @return array ['has_issues' => bool, 'issues' => array]
     */
    public function checkStockIssues(Collection $items): array
    {
        $issues = [];

        foreach ($items as $item) {
            $productId = is_object($item) ? $item->product_id : $item['product_id'];
            $quantity = is_object($item) ? $item->quantity : $item['quantity'];

            $product = is_object($item) && $item->relationLoaded('product')
                ? $item->product
                : Product::find($productId);

            if (!$product) {
                $issues[] = [
                    'product_id' => $productId,
                    'product_name' => 'Produit introuvable',
                    'message' => 'Ce produit n\'existe plus.',
                ];
                continue;
            }

            if (!$product->is_active) {
                $issues[] = [
                    'product_id' => $productId,
                    'product_name' => $product->title,
                    'message' => 'Ce produit n\'est plus disponible.',
                ];
                continue;
            }

            if ($product->stock < $quantity) {
                $issues[] = [
                    'product_id' => $productId,
                    'product_name' => $product->title,
                    'message' => "Stock insuffisant. Il ne reste que {$product->stock} exemplaire(s) disponible(s).",
                    'available_stock' => $product->stock,
                    'requested_quantity' => $quantity,
                ];
            }
        }

        return [
            'has_issues' => count($issues) > 0,
            'issues' => $issues,
        ];
    }
}


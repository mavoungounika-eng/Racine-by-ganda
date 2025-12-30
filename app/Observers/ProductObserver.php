<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\NotificationService;
use App\Services\ProductCodeService;

class ProductObserver
{
    protected NotificationService $notificationService;
    protected ProductCodeService $productCodeService;

    public function __construct(NotificationService $notificationService, ProductCodeService $productCodeService)
    {
        $this->notificationService = $notificationService;
        $this->productCodeService = $productCodeService;
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Générer automatiquement SKU et code-barres
        $this->productCodeService->createOrUpdateProductDetails($product->id);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Vérifier si le stock a changé
        if ($product->isDirty('stock')) {
            $this->handleStockChange($product);
        }
    }

    /**
     * Gérer le changement de stock
     */
    protected function handleStockChange(Product $product): void
    {
        $previousStock = $product->getOriginal('stock');
        $currentStock = $product->stock;

        // Alerte rupture de stock
        if ($currentStock <= 0 && $previousStock > 0) {
            $this->notificationService->broadcastToTeam(
                'Rupture de stock ! 🚨',
                "Le produit \"{$product->title}\" est en rupture de stock.",
                'danger'
            );
        }
        // Alerte stock faible
        elseif ($currentStock > 0 && $currentStock <= 5 && $previousStock > 5) {
            $this->notificationService->broadcastToTeam(
                'Stock faible ⚠️',
                "Le produit \"{$product->title}\" n'a plus que {$currentStock} unité(s) en stock.",
                'warning'
            );
        }
        // Retour en stock
        elseif ($currentStock > 0 && $previousStock <= 0) {
            $this->notificationService->broadcastToTeam(
                'Retour en stock ✅',
                "Le produit \"{$product->title}\" est de nouveau disponible ({$currentStock} unités).",
                'success'
            );
        }
    }
}


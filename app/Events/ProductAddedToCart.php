<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un produit est ajouté au panier
 * 
 * Utilisé pour le monitoring du funnel d'achat (Phase 3)
 */
class ProductAddedToCart
{
    use Dispatchable, SerializesModels;

    public Product $product;
    public ?int $userId;
    public int $quantity;

    /**
     * Create a new event instance.
     */
    public function __construct(Product $product, ?int $userId, int $quantity)
    {
        $this->product = $product;
        $this->userId = $userId;
        $this->quantity = $quantity;
    }
}

<?php

namespace App\Services\Cart;

use App\Models\Product;

class CartMergerService
{
    protected $sessionCart;
    protected $databaseCart;

    public function __construct(SessionCartService $sessionCart, DatabaseCartService $databaseCart)
    {
        $this->sessionCart = $sessionCart;
        $this->databaseCart = $databaseCart;
    }

    public function merge(): void
    {
        $sessionItems = $this->sessionCart->getItems();

        if ($sessionItems->isEmpty()) {
            return;
        }

        foreach ($sessionItems as $item) {
            $product = Product::where('id', $item['product_id'])
                ->where('is_active', true)
                ->first();
            if ($product && $product->stock > 0) {
                $this->databaseCart->add($product, $item['quantity']);
            }
        }

        $this->sessionCart->clear();
    }
}

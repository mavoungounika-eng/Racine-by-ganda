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
            $product = Product::find($item['product_id']);
            if ($product) {
                $this->databaseCart->add($product, $item['quantity']);
            }
        }

        $this->sessionCart->clear();
    }
}

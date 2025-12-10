<?php

namespace App\Services\Cart;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class SessionCartService
{
    protected $sessionKey = 'cart';

    public function getItems(): Collection
    {
        return collect(Session::get($this->sessionKey, []));
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getItems();

        if ($cart->has($product->id)) {
            $item = $cart->get($product->id);
            $newQuantity = $item['quantity'] + $quantity;
            
            // Vérifier que la nouvelle quantité totale ne dépasse pas le stock
            if ($newQuantity > $product->stock) {
                // Limiter au stock disponible
                $item['quantity'] = $product->stock;
            } else {
                $item['quantity'] = $newQuantity;
            }
            
            $cart->put($product->id, $item);
        } else {
            $cart->put($product->id, [
                'product_id' => $product->id,
                'title' => $product->title,
                'price' => $product->price,
                'quantity' => $quantity,
                'main_image' => $product->main_image,
                'slug' => $product->slug,
            ]);
        }

        Session::put($this->sessionKey, $cart->all());
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->getItems();

        if ($cart->has($productId)) {
            if ($quantity <= 0) {
                $this->remove($productId);
                return;
            }

            $item = $cart->get($productId);
            $item['quantity'] = $quantity;
            $cart->put($productId, $item);
            Session::put($this->sessionKey, $cart->all());
        }
    }

    public function remove(int $productId): void
    {
        $cart = $this->getItems();
        $cart->forget($productId);
        Session::put($this->sessionKey, $cart->all());
    }

    public function clear(): void
    {
        Session::forget($this->sessionKey);
    }

    public function total(): float
    {
        return $this->getItems()->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    public function count(): int
    {
        return $this->getItems()->sum('quantity');
    }
}

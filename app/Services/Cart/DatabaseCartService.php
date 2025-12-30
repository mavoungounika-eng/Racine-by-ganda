<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DatabaseCartService
{
    public function getCart(): ?Cart
    {
        if (!Auth::check()) {
            return null;
        }

        return Cart::firstOrCreate(['user_id' => Auth::id()]);
    }

    public function getItems(): Collection
    {
        $cart = $this->getCart();
        return $cart ? $cart->items()->with('product')->get() : collect();
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        if (!$cart) return;

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            // Vérifier que la nouvelle quantité totale ne dépasse pas le stock
            $newQuantity = $item->quantity + $quantity;
            if ($newQuantity > $product->stock) {
                // Limiter au stock disponible
                $item->update(['quantity' => $product->stock]);
            } else {
                $item->increment('quantity', $quantity);
            }
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
            ]);
        }
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->getCart();
        if (!$cart) return;

        if ($quantity <= 0) {
            $this->remove($productId);
            return;
        }

        $item = $cart->items()->where('product_id', $productId)->first();
        if ($item) {
            $item->update(['quantity' => $quantity]);
        }
    }

    public function remove(int $productId): void
    {
        $cart = $this->getCart();
        if (!$cart) return;

        $cart->items()->where('product_id', $productId)->delete();
    }

    public function clear(): void
    {
        $cart = $this->getCart();
        if ($cart) {
            $cart->items()->delete();
        }
    }

    public function total(): float
    {
        return $this->getItems()->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }

    public function count(): int
    {
        return $this->getItems()->sum('quantity');
    }
}

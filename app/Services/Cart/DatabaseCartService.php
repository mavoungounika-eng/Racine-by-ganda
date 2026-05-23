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
        return $cart ? $cart->items()->with(['product.category', 'product.creator', 'product.mainImage'])->get() : collect();
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $cart = $this->getCart();
        if (!$cart) return;

        $item = $cart->items()->where('product_id', $product->id)->first();

        if ($item) {
            $newQuantity = $item->quantity + $quantity;
            if ($newQuantity > $product->stock) {
                $item->update(['quantity' => $product->stock]);
            } else {
                $item->increment('quantity', $quantity);
            }
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'quantity'   => $quantity,
                'price'      => $product->price,
            ]);
        }

        // Reset reminder state — new activity means the cart is no longer abandoned
        $cart->update(['reminder_count' => 0, 'last_reminder_sent_at' => null]);
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

    /**
     * Resynchronise les prix du panier avec les prix actuels en DB.
     * Retourne la liste des items dont le prix a changé avec les anciens/nouveaux prix.
     */
    public function refreshPrices(): array
    {
        $cart = $this->getCart();
        if (!$cart) {
            return [];
        }

        $changes = [];
        foreach ($cart->items()->with('product')->get() as $item) {
            if (!$item->product) {
                continue;
            }
            $currentPrice = (float) $item->product->price;
            $storedPrice  = (float) $item->price;
            if (abs($currentPrice - $storedPrice) > 0.01) {
                $changes[] = [
                    'product_name' => $item->product->title,
                    'old_price'    => $storedPrice,
                    'new_price'    => $currentPrice,
                ];
                $item->update(['price' => $currentPrice]);
            }
        }

        return $changes;
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

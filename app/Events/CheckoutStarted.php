<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un utilisateur commence le checkout
 * 
 * Utilisé pour le monitoring du funnel d'achat (Phase 3)
 */
class CheckoutStarted
{
    use Dispatchable, SerializesModels;

    public ?int $userId;
    public int $cartItemsCount;
    public float $cartTotal;

    /**
     * Create a new event instance.
     */
    public function __construct(?int $userId, int $cartItemsCount, float $cartTotal)
    {
        $this->userId = $userId;
        $this->cartItemsCount = $cartItemsCount;
        $this->cartTotal = $cartTotal;
    }
}

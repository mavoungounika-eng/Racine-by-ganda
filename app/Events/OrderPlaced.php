<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'une commande est créée
 * 
 * Utilisé pour le monitoring du funnel d'achat (Phase 3)
 */
class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public Order $order;
    public ?int $userId;
    public string $paymentMethod;
    public float $totalAmount;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->userId = $order->user_id;
        $this->paymentMethod = $order->payment_method ?? 'unknown';
        $this->totalAmount = $order->total_amount;
    }
}

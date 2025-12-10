<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un paiement échoue
 * 
 * Utilisé pour le monitoring du funnel d'achat (Phase 3)
 */
class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public Order $order;
    public ?int $userId;
    public string $paymentMethod;
    public ?string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, ?string $reason = null)
    {
        $this->order = $order;
        $this->userId = $order->user_id;
        $this->paymentMethod = $order->payment_method ?? 'unknown';
        $this->reason = $reason;
    }
}

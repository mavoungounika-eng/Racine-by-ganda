<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un paiement est complété avec succès
 * 
 * Utilisé pour le monitoring du funnel d'achat (Phase 3)
 */
class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public Order $order;
    public Payment $payment;
    public ?int $userId;
    public string $paymentMethod;
    public float $amount;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
        $this->userId = $order->user_id;
        $this->paymentMethod = $order->payment_method ?? 'unknown';
        $this->amount = $payment->amount;
    }
}

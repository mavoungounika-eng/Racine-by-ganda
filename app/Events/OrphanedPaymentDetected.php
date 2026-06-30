<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event déclenché lorsqu'un paiement est reçu
 * pour une commande déjà annulée ou terminale.
 */
class OrphanedPaymentDetected
{
    use Dispatchable, SerializesModels;

    public Order $order;
    public Payment $payment;

    public function __construct(Order $order, Payment $payment)
    {
        $this->order = $order;
        $this->payment = $payment;
    }
}

<?php

namespace App\Events;

use App\Models\PosPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a POS card payment is confirmed
 * 
 * Triggers:
 * - Création PosCardPaymentIntent
 * - Écriture comptable via LedgerService
 */
class PosCardPaymentConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosPayment $payment
    ) {}
}

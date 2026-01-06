<?php

namespace App\Events;

use App\Models\PosPayment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a POS mobile money payment is confirmed
 * 
 * Triggers:
 * - Création PosMobilePaymentIntent
 * - Écriture comptable via LedgerService
 */
class PosMobilePaymentConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosPayment $payment
    ) {}
}

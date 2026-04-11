<?php

namespace App\Events;

use App\Models\PosSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché lorsqu'une discrepancy cash est détectée
 * lors de la clôture d'une session POS
 */
class CashDiscrepancyDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosSession $session,
        public float $expectedCash,
        public float $actualCash,
        public float $difference
    ) {}
}
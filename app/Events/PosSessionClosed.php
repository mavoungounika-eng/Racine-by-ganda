<?php

namespace App\Events;

use App\Models\PosSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a POS session is closed
 * 
 * Triggers:
 * - Création PosCashSettlementIntent pour toutes ventes cash
 * - Finalisation des ventes cash de la session
 */
class PosSessionClosed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PosSession $session
    ) {}
}

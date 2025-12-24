<?php

namespace Modules\Accounting\Events;

use Modules\ERP\Models\ErpPurchase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseReceived
{
    use Dispatchable, SerializesModels;

    public ErpPurchase $purchase;

    /**
     * Create a new event instance.
     */
    public function __construct(ErpPurchase $purchase)
    {
        $this->purchase = $purchase;
    }
}

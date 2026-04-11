<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\ProductionOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionFinished
{
    use Dispatchable, SerializesModels;

    public ProductionOrder $productionOrder;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductionOrder $productionOrder)
    {
        $this->productionOrder = $productionOrder;
    }
}

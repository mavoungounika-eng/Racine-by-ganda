<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\ProductionOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionScrapped
{
    use Dispatchable, SerializesModels;

    public ProductionOrder $productionOrder;
    public float $scrapQuantity;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductionOrder $productionOrder, float $scrapQuantity, string $reason)
    {
        $this->productionOrder = $productionOrder;
        $this->scrapQuantity = $scrapQuantity;
        $this->reason = $reason;
    }
}

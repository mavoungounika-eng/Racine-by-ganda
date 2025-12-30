<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\ProductionCost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionCostCalculated
{
    use Dispatchable, SerializesModels;

    public ProductionCost $productionCost;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductionCost $productionCost)
    {
        $this->productionCost = $productionCost;
    }
}

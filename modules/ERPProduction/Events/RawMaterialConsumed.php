<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\ProductionOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RawMaterialConsumed
{
    use Dispatchable, SerializesModels;

    public ProductionOrder $productionOrder;
    public array $materials; // Liste matières consommées avec quantités et coûts

    /**
     * Create a new event instance.
     */
    public function __construct(ProductionOrder $productionOrder, array $materials)
    {
        $this->productionOrder = $productionOrder;
        $this->materials = $materials;
    }
}

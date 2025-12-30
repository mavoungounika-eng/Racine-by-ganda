<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\ProductionOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockScrapped
{
    use Dispatchable, SerializesModels;

    public ProductionOrder $productionOrder;
    public float $quantity;
    public float $unitCost;
    public float $totalCost;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ProductionOrder $productionOrder,
        float $quantity,
        float $unitCost,
        float $totalCost,
        string $reason
    ) {
        $this->productionOrder = $productionOrder;
        $this->quantity = $quantity;
        $this->unitCost = $unitCost;
        $this->totalCost = $totalCost;
        $this->reason = $reason;
    }
}

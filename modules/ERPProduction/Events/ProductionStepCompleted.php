<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\WorkStep;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductionStepCompleted
{
    use Dispatchable, SerializesModels;

    public WorkStep $workStep;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkStep $workStep)
    {
        $this->workStep = $workStep;
    }
}

<?php

namespace Modules\ERPProduction\Events;

use Modules\ERPProduction\Models\QualityCheck;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QualityCheckPassed
{
    use Dispatchable, SerializesModels;

    public QualityCheck $qualityCheck;

    /**
     * Create a new event instance.
     */
    public function __construct(QualityCheck $qualityCheck)
    {
        $this->qualityCheck = $qualityCheck;
    }
}

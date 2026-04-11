<?php

namespace Modules\POSSync\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleFinalized
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public array $saleData
    ) {}
}

<?php

namespace Modules\Accounting\Events;

use App\Models\CreatorPayout;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CreatorPayoutProcessed
{
    use Dispatchable, SerializesModels;

    public CreatorPayout $payout;

    /**
     * Create a new event instance.
     */
    public function __construct(CreatorPayout $payout)
    {
        $this->payout = $payout;
    }
}

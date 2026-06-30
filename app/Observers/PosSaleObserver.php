<?php

namespace App\Observers;

use App\Models\PosSale;
use Illuminate\Support\Facades\Cache;

class PosSaleObserver
{
    /**
     * Handle the PosSale "created" event.
     */
    public function created(PosSale $sale): void
    {
        $this->clearAnalyticsCache($sale);
    }

    /**
     * Handle the PosSale "updated" event.
     */
    public function updated(PosSale $sale): void
    {
        $this->clearAnalyticsCache($sale);
    }

    private function clearAnalyticsCache(PosSale $sale): void
    {
        $date = $sale->created_at->format('Y-m-d');
        Cache::forget("pos:analytics:daily:{$sale->machine_id}:{$date}");
    }
}

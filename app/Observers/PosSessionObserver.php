<?php

namespace App\Observers;

use App\Models\PosSession;
use Illuminate\Support\Facades\Cache;

class PosSessionObserver
{
    /**
     * Handle the PosSession "created" event.
     */
    public function created(PosSession $session): void
    {
        $this->clearAnalyticsCache($session);
    }

    /**
     * Handle the PosSession "updated" event.
     */
    public function updated(PosSession $session): void
    {
        $this->clearAnalyticsCache($session);
    }

    /**
     * Handle the PosSession "deleted" event.
     */
    public function deleted(PosSession $session): void
    {
        $this->clearAnalyticsCache($session);
    }

    private function clearAnalyticsCache(PosSession $session): void
    {
        // Flush daily summary for this machine
        $date = $session->opened_at->format('Y-m-d');
        Cache::forget("pos:analytics:daily:{$session->machine_id}:{$date}");
        
        // Active sessions aren't cached, period reports/top products are harder to selectively invalidate
        // but we can flush general keys or rely on TTL. Due to lack of wildcard forget in file cache,
        // we'll let period/top products expire naturally via TTL or use Cache::flush() if strictly needed.
        // For safe measure, in a real Redis setup we'd use tags. Setting basic ones here.
    }
}

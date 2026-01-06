<?php

namespace App\Listeners;

use App\Events\PosSessionClosed;
use App\Services\Pos\PosFinanceIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for PosSessionClosed event
 * 
 * Creates PosCashSettlementIntent for all cash sales in the session
 * and commits the intent to create accounting entries.
 */
class PosSessionClosedListener implements ShouldQueue
{
    public function __construct(
        protected PosFinanceIntegrationService $financeService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PosSessionClosed $event): void
    {
        $session = $event->session;

        Log::info('PosSessionClosedListener: Processing session closure', [
            'session_id' => $session->id,
            'machine_id' => $session->machine_id,
        ]);

        try {
            // Créer l'intent pour le settlement cash
            $intent = $this->financeService->createCashSettlementIntent($session);

            // Commiter l'intent (créer écriture comptable)
            if ($intent->canProcess()) {
                $entry = $this->financeService->commitIntent($intent);

                Log::info('PosSessionClosedListener: Cash settlement intent committed', [
                    'session_id' => $session->id,
                    'intent_id' => $intent->id,
                    'entry_id' => $entry->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PosSessionClosedListener: Failed to process session closure', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Re-throw pour retry du job
        }
    }

    /**
     * Determine the time to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [60, 300, 900]; // 1min, 5min, 15min
    }

    /**
     * Determine how many times the job may be attempted.
     */
    public function tries(): int
    {
        return 5;
    }
}

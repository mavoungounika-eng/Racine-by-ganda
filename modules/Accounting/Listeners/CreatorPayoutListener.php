<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Events\CreatorPayoutProcessed;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CreatorPayoutListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected LedgerService $ledgerService;

    /**
     * Create the event listener.
     */
    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Handle the event.
     */
    public function handle(CreatorPayoutProcessed $event): void
    {
        $payout = $event->payout;

        // Vérifier que le payout est confirmé
        if ($payout->status !== 'paid') {
            Log::info('CreatorPayoutListener: Payout not confirmed, skipping accounting entry', [
                'payout_id' => $payout->id,
                'status' => $payout->status,
            ]);
            return;
        }

        try {
            $this->createPayoutEntry($payout);

            Log::info('CreatorPayoutListener: Accounting entry created successfully', [
                'payout_id' => $payout->id,
                'creator_id' => $payout->creator_id,
                'amount' => $payout->amount,
            ]);
        } catch (LedgerException $e) {
            Log::error('CreatorPayoutListener: Failed to create accounting entry', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw pour retry (ShouldQueue)
            throw $e;
        }
    }

    /**
     * Créer écriture payout créateur
     */
    protected function createPayoutEntry($payout): void
    {
        $journal = Journal::where('code', 'BNQ')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Payout créateur #{$payout->creator_id} - {$payout->creator->name}",
            'reference_type' => 'creator_payout',
            'reference_id' => $payout->id,
        ]);

        // Ligne 1: Débit dette créateur (4671)
        $this->ledgerService->addLine(
            $entry,
            '4671',
            $payout->amount,
            0,
            "Règlement créateur {$payout->creator->name}"
        );

        // Ligne 2: Crédit banque Stripe (5211)
        $this->ledgerService->addLine(
            $entry,
            '5211',
            0,
            $payout->amount,
            "Virement Stripe Connect"
        );

        // Poster automatiquement
        $this->ledgerService->postEntry($entry);
    }

    /**
     * Handle a job failure.
     */
    public function failed(CreatorPayoutProcessed $event, \Throwable $exception): void
    {
        Log::error('CreatorPayoutListener: Job failed permanently', [
            'payout_id' => $event->payout->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

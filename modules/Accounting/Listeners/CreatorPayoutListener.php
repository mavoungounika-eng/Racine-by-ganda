<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Events\CreatorPayoutProcessed;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\LedgerException;
use App\Services\Financial\AccountingIdempotenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour créer les écritures comptables suite à un payout créateur.
 * 
 * GARANTIES D'IDEMPOTENCE:
 * 1. Vérification existence écriture AVANT création
 * 2. Retour silencieux si déjà existante
 * 3. Contrainte UNIQUE DB comme filet de sécurité
 * 4. Logging métier des collisions
 */
class CreatorPayoutListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected LedgerService $ledgerService;

    /**
     * Nombre de tentatives maximum
     */
    public int $tries = 3;

    /**
     * Backoff entre tentatives (secondes)
     */
    public array $backoff = [10, 30, 60];

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

        // GUARD 1: Vérifier que le payout est confirmé
        if ($payout->status !== 'paid') {
            Log::info('CreatorPayoutListener: Payout not confirmed, skipping', [
                'payout_id' => $payout->id,
                'status' => $payout->status,
            ]);
            return;
        }

        // GUARD 2: IDEMPOTENCE - Vérifier si écriture existe déjà
        $existingEntry = AccountingEntry::where('reference_type', 'creator_payout')
            ->where('reference_id', $payout->id)
            ->whereNull('deleted_at')
            ->first();

        if ($existingEntry) {
            // Collision détectée - enregistrer et retourner silencieusement
            AccountingIdempotenceService::recordCollision(
                referenceType: 'creator_payout',
                referenceId: $payout->id,
                listener: self::class,
                existingEntryId: $existingEntry->id
            );

            Log::info('CreatorPayoutListener: Entry already exists (idempotence guard)', [
                'payout_id' => $payout->id,
                'existing_entry_id' => $existingEntry->id,
                'existing_entry_number' => $existingEntry->entry_number,
            ]);

            return; // Retour silencieux - pas d'erreur, pas de retry
        }

        try {
            $this->createPayoutEntry($payout);

            Log::info('CreatorPayoutListener: Accounting entry created', [
                'payout_id' => $payout->id,
                'creator_id' => $payout->creator_id,
                'amount' => $payout->amount,
            ]);

        } catch (LedgerException $e) {
            Log::error('CreatorPayoutListener: Failed to create entry', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw pour retry
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

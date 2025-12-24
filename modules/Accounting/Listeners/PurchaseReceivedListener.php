<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Events\PurchaseReceived;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PurchaseReceivedListener implements ShouldQueue
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
    public function handle(PurchaseReceived $event): void
    {
        $purchase = $event->purchase;

        // Vérifier que l'achat est reçu
        if ($purchase->status !== 'received') {
            Log::info('PurchaseReceivedListener: Purchase not received, skipping accounting entry', [
                'purchase_id' => $purchase->id,
                'status' => $purchase->status,
            ]);
            return;
        }

        try {
            $this->createPurchaseEntry($purchase);

            Log::info('PurchaseReceivedListener: Accounting entry created successfully', [
                'purchase_id' => $purchase->id,
                'total' => $purchase->total,
            ]);
        } catch (LedgerException $e) {
            Log::error('PurchaseReceivedListener: Failed to create accounting entry', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw pour retry (ShouldQueue)
            throw $e;
        }
    }

    /**
     * Créer écriture achat matières premières
     */
    protected function createPurchaseEntry($purchase): void
    {
        $journal = Journal::where('code', 'ACH')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        // Calculer HT et TVA
        $totalTTC = $purchase->total;
        $vatRate = 18.0;
        $amountHT = $totalTTC / (1 + $vatRate / 100);
        $vatAmount = $totalTTC - $amountHT;

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Achat matières premières #{$purchase->id} - {$purchase->supplier->name}",
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);

        // Ligne 1: Débit achats (HT)
        $this->ledgerService->addLine($entry, '6011', $amountHT, 0, "Achats tissus HT", [
            'amount_ht' => $amountHT,
            'vat_amount' => $vatAmount,
            'vat_rate' => $vatRate,
        ]);

        // Ligne 2: Débit TVA déductible
        $this->ledgerService->addLine($entry, '4422', $vatAmount, 0, "TVA déductible {$vatRate}%");

        // Ligne 3: Crédit fournisseur (TTC)
        $this->ledgerService->addLine($entry, '4011', 0, $totalTTC, "Fournisseur {$purchase->supplier->name}");

        // Poster automatiquement
        $this->ledgerService->postEntry($entry);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PurchaseReceived $event, \Throwable $exception): void
    {
        Log::error('PurchaseReceivedListener: Job failed permanently', [
            'purchase_id' => $event->purchase->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

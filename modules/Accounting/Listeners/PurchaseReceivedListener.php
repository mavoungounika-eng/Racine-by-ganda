<?php

namespace Modules\Accounting\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Accounting\Events\PurchaseReceived;
use Modules\Accounting\Exceptions\LedgerException;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Services\LedgerService;

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

        // Book entries only when purchase is effectively received.
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
                'total_amount' => $purchase->total_amount,
            ]);
        } catch (LedgerException $e) {
            Log::error('PurchaseReceivedListener: Failed to create accounting entry', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw so queue retries still apply.
            throw $e;
        }
    }

    /**
     * Create accounting entry for raw material purchase.
     */
    protected function createPurchaseEntry($purchase): void
    {
        $journal = Journal::where('code', 'ACH')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        $totalTTC = (float) ($purchase->total_amount ?? 0);
        $vatRate = 18.0;
        $amountHT = $totalTTC / (1 + $vatRate / 100);
        $vatAmount = $totalTTC - $amountHT;
        $supplierName = $purchase->supplier?->name ?? 'Fournisseur inconnu';

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Achat matieres premieres #{$purchase->id} - {$supplierName}",
            'reference_type' => 'purchase',
            'reference_id' => $purchase->id,
        ]);

        // Debit purchases (HT)
        $this->ledgerService->addLine($entry, '6011', $amountHT, 0, 'Achats tissus HT', [
            'amount_ht' => $amountHT,
            'vat_amount' => $vatAmount,
            'vat_rate' => $vatRate,
        ]);

        // Debit deductible VAT
        $this->ledgerService->addLine($entry, '4422', $vatAmount, 0, "TVA deductible {$vatRate}%");

        // Credit supplier (TTC)
        $this->ledgerService->addLine($entry, '4011', 0, $totalTTC, "Fournisseur {$supplierName}");

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

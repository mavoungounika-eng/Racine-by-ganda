<?php

namespace Modules\Accounting\Listeners;

use Modules\ERPProduction\Events\ProductionScrapped;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProductionScrappedListener implements ShouldQueue
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
    public function handle(ProductionScrapped $event): void
    {
        $order = $event->productionOrder;
        $scrapQuantity = $event->scrapQuantity;
        $reason = $event->reason;

        try {
            $this->createScrapEntry($order, $scrapQuantity, $reason);

            Log::info('ProductionScrappedListener: Accounting entry created', [
                'production_order_id' => $order->id,
                'scrap_quantity' => $scrapQuantity,
                'reason' => $reason,
            ]);
        } catch (LedgerException $e) {
            Log::error('ProductionScrappedListener: Failed to create accounting entry', [
                'production_order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Créer écriture rebut production
     * Débit 6xx (Pertes sur production) / Crédit 331 (En-cours)
     */
    protected function createScrapEntry($order, float $scrapQuantity, string $reason): void
    {
        $journal = Journal::where('code', 'OD')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        // Calculer coût rebut
        $unitCost = $order->bom->calculateUnitMaterialCost();
        $scrapCost = $unitCost * $scrapQuantity;

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Rebut production {$order->order_number} - {$reason}",
            'reference_type' => 'production_order',
            'reference_id' => $order->id,
        ]);

        // Débit Pertes sur production (compte 6xx - à créer si nécessaire)
        // Pour l'instant, utiliser compte générique charges
        $this->ledgerService->addLine(
            $entry,
            '658', // Charges diverses (à adapter selon plan comptable)
            $scrapCost,
            0,
            "Perte production - {$scrapQuantity} unités"
        );

        // Crédit En-cours de production (331)
        $this->ledgerService->addLine(
            $entry,
            '331',
            0,
            $scrapCost,
            "Sortie en-cours (rebut)"
        );

        // Poster automatiquement
        $this->ledgerService->postEntry($entry);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductionScrapped $event, \Throwable $exception): void
    {
        Log::error('ProductionScrappedListener: Job failed permanently', [
            'production_order_id' => $event->productionOrder->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

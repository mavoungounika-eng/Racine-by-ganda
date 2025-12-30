<?php

namespace Modules\Accounting\Listeners;

use Modules\ERPProduction\Events\ProductionFinished;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProductionFinishedListener implements ShouldQueue
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
    public function handle(ProductionFinished $event): void
    {
        $order = $event->productionOrder;

        try {
            $this->createProductionFinishEntry($order);

            Log::info('ProductionFinishedListener: Accounting entry created', [
                'production_order_id' => $order->id,
                'order_number' => $order->order_number,
                'quantity_produced' => $order->quantity_produced,
            ]);
        } catch (LedgerException $e) {
            Log::error('ProductionFinishedListener: Failed to create accounting entry', [
                'production_order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Créer écriture fin production
     * Débit 351 (Produits finis) / Crédit 331 (En-cours)
     */
    protected function createProductionFinishEntry($order): void
    {
        $journal = Journal::where('code', 'OD')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        // Calculer coût production (matières + main d'œuvre estimée)
        $materialCost = $order->bom->calculateTotalMaterialCost() * $order->quantity_produced;
        
        // Pour l'instant, coût production = coût matières
        // Dans Sprint 11-12 (Costing), on ajoutera main d'œuvre + charges
        $productionCost = $materialCost;

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Fin production {$order->order_number} - {$order->product->name}",
            'reference_type' => 'production_order',
            'reference_id' => $order->id,
        ]);

        // Débit Produits finis (351)
        $this->ledgerService->addLine(
            $entry,
            '351',
            $productionCost,
            0,
            "Entrée stock produits finis - {$order->quantity_produced} unités"
        );

        // Crédit En-cours de production (331)
        $this->ledgerService->addLine(
            $entry,
            '331',
            0,
            $productionCost,
            "Sortie en-cours"
        );

        // Poster automatiquement
        $this->ledgerService->postEntry($entry);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductionFinished $event, \Throwable $exception): void
    {
        Log::error('ProductionFinishedListener: Job failed permanently', [
            'production_order_id' => $event->productionOrder->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

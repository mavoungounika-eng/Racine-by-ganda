<?php

namespace Modules\Accounting\Listeners;

use Modules\ERPProduction\Events\ProductionStarted;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProductionStartedListener implements ShouldQueue
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
    public function handle(ProductionStarted $event): void
    {
        $order = $event->productionOrder;

        try {
            $this->createProductionStartEntry($order);

            Log::info('ProductionStartedListener: Accounting entry created', [
                'production_order_id' => $order->id,
                'order_number' => $order->order_number,
                'quantity' => $order->quantity_planned,
            ]);
        } catch (LedgerException $e) {
            Log::error('ProductionStartedListener: Failed to create accounting entry', [
                'production_order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Créer écriture démarrage production
     * Débit 331 (En-cours) / Crédit 311 (Matières premières)
     */
    protected function createProductionStartEntry($order): void
    {
        $journal = Journal::where('code', 'OD')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        // Calculer coût matières (BOM)
        $materialCost = $order->bom->calculateTotalMaterialCost() * $order->quantity_planned;

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Démarrage production {$order->order_number} - {$order->product->name}",
            'reference_type' => 'production_order',
            'reference_id' => $order->id,
        ]);

        // Débit En-cours de production (331)
        $this->ledgerService->addLine(
            $entry,
            '331',
            $materialCost,
            0,
            "Entrée en-cours - {$order->quantity_planned} unités"
        );

        // Crédit Matières premières (311)
        $this->ledgerService->addLine(
            $entry,
            '311',
            0,
            $materialCost,
            "Consommation matières"
        );

        // Poster automatiquement
        $this->ledgerService->postEntry($entry);
    }

    /**
     * Handle a job failure.
     */
    public function failed(ProductionStarted $event, \Throwable $exception): void
    {
        Log::error('ProductionStartedListener: Job failed permanently', [
            'production_order_id' => $event->productionOrder->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

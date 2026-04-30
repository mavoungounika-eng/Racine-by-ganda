<?php

namespace Tests\Feature\Accounting;

use Illuminate\Support\Facades\Event;
use Modules\Accounting\Events\PurchaseReceived;
use Modules\Accounting\Listeners\ProductionFinishedListener;
use Modules\Accounting\Listeners\ProductionScrappedListener;
use Modules\Accounting\Listeners\ProductionStartedListener;
use Modules\Accounting\Listeners\PurchaseReceivedListener;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Observers\ErpPurchaseObserver;
use Modules\ERPProduction\Events\ProductionFinished;
use Modules\ERPProduction\Events\ProductionScrapped;
use Modules\ERPProduction\Events\ProductionStarted;
use Tests\TestCase;

class ErpAccountingWiringTest extends TestCase
{
    public function test_it_registers_accounting_listeners_for_erp_events(): void
    {
        Event::fake();

        Event::assertListening(PurchaseReceived::class, PurchaseReceivedListener::class);
        Event::assertListening(ProductionStarted::class, ProductionStartedListener::class);
        Event::assertListening(ProductionFinished::class, ProductionFinishedListener::class);
        Event::assertListening(ProductionScrapped::class, ProductionScrappedListener::class);
    }

    public function test_it_dispatches_purchase_received_when_purchase_status_changes_to_received(): void
    {
        Event::fake([PurchaseReceived::class]);

        $purchase = new ErpPurchase([
            'status' => 'ordered',
            'total_amount' => 590.00,
        ]);
        $purchase->id = 1;
        $purchase->supplier_id = 10;
        $purchase->syncOriginal();
        $purchase->status = 'received';
        $purchase->syncChanges();

        (new ErpPurchaseObserver())->updated($purchase);

        Event::assertDispatched(PurchaseReceived::class, function (PurchaseReceived $event) use ($purchase) {
            return $event->purchase === $purchase;
        });
    }
}

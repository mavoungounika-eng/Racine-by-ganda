<?php

namespace Modules\ERP\Observers;

use Modules\ERP\Models\ErpPurchase;
use Modules\Accounting\Events\PurchaseReceived;

class ErpPurchaseObserver
{
    /**
     * Handle the ErpPurchase "updated" event.
     */
    public function updated(ErpPurchase $purchase): void
    {
        // Vérifier si le statut a changé vers 'received'
        if ($purchase->isDirty('status') && $purchase->status === 'received') {
            // Dispatch événement pour comptabilité
            event(new PurchaseReceived($purchase));
            
            \Log::info('ErpPurchaseObserver: Purchase received, accounting event dispatched', [
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'total' => $purchase->total,
            ]);
        }
    }
}

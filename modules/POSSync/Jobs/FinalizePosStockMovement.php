<?php

namespace Modules\POSSync\Jobs;

use App\Models\Order;
use App\Models\PosSale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Services\StockService as ErpStockService;

class FinalizePosStockMovement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload
    ) {}

    public function handle(ErpStockService $stockService): void
    {
        $saleId = $this->payload['sale_id'] ?? $this->payload['pos_sale_id'] ?? null;
        $orderId = $this->payload['order_id'] ?? null;

        $sale = $saleId ? PosSale::with('order.items')->find($saleId) : null;
        $order = $sale?->order;

        if (!$order && $orderId) {
            $order = Order::with('items')->find($orderId);
        }

        if (!$order) {
            Log::warning('FinalizePosStockMovement skipped: order not found', [
                'payload' => $this->payload,
            ]);
            return;
        }

        // Idempotent in ERP StockService (skips if movement already exists).
        $stockService->decrementFromOrder($order);

        if ($sale && !$sale->isFinalized()) {
            $sale->finalize();
        }

        Log::info('FinalizePosStockMovement processed', [
            'sale_id' => $sale?->id,
            'order_id' => $order->id,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FinalizePosStockMovement failed', [
            'payload' => $this->payload,
            'error' => $exception->getMessage(),
        ]);
    }
}

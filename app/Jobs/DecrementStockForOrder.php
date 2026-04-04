<?php

namespace App\Jobs;

use App\Events\StockAnomalyDetected;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Services\StockService;

/**
 * Job de décrément asynchrone du stock pour une commande web.
 *
 * NOTE: OrderService décrémente déjà le stock de façon synchrone via OrderObserver.
 * Ce Job est prévu pour les scénarios haute charge uniquement (usage futur).
 * Ne PAS l'enregistrer dans OrderService pour éviter le double décrément.
 */
class DecrementStockForOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [5, 15, 30];

    public function __construct(
        public readonly Order $order,
    ) {
        $this->onQueue(config('erp.stock_queue', 'stock'));
    }

    /**
     * Exécuter le job.
     */
    public function handle(StockService $stockService): void
    {
        Log::info("DecrementStockForOrder: processing order #{$this->order->id}");
        $stockService->decrementFromOrder($this->order);
    }

    /**
     * Gérer l'échec définitif du job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('erp_stock')->critical("DecrementStockForOrder job failed definitively for Order #{$this->order->id}: " . $exception->getMessage());

        event(new StockAnomalyDetected(
            product_id:      0,
            order_id:        $this->order->id,
            requested_qty:   0,
            available_stock: 0,
            detected_at:     now()->toIso8601String(),
        ));
    }
}

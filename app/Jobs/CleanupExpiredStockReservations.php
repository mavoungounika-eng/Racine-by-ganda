<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredStockReservations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Délai d'expiration des réservations (en minutes)
     */
    protected int $expirationMinutes = 30;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     * 
     * Libère les réservations stock pour les commandes :
     * - Créées il y a plus de 30 minutes
     * - Statut = pending
     * - Payment_status = pending
     */
    public function handle(): void
    {
        $expiredAt = now()->subMinutes($this->expirationMinutes);
        $stockService = app(\Modules\ERP\Services\StockService::class);

        // Trouver les commandes expirées avec réservations
        // EXCLUSIONS :
        // - Cash on delivery (délai plus long géré par CleanupAbandonedOrders)
        // - Paiements en cours (table payments)
        $expiredOrders = Order::where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('created_at', '<=', $expiredAt)
            ->where('payment_method', '!=', 'cash_on_delivery') // Exclusion COD
            ->whereDoesntHave('payments', function ($query) {
                $query->where('status', 'pending');
            })
            ->with('items')
            ->get();

        if ($expiredOrders->isEmpty()) {
            Log::info('No expired stock reservations to cleanup');
            return;
        }

        $releasedCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                DB::transaction(function () use ($order, &$releasedCount, $stockService) {
                    // 1. Libérer la réservation (stock_reserved)
                    foreach ($order->items as $item) {
                        $product = Product::lockForUpdate()->find($item->product_id);
                        
                        if (!$product) {
                            continue;
                        }

                        if ($product->stock_reserved >= $item->quantity) {
                            $product->decrement('stock_reserved', $item->quantity);
                            $releasedCount++;
                        }
                    }

                    // 2. Réintégrer le stock physique via ERP (stock)
                    $stockService->restockFromOrder($order);

                    // 3. Marquer la commande comme expirée
                    $oldStatus = $order->status;
                    $order->update([
                        'status' => 'cancelled',
                        'payment_status' => 'failed',
                    ]);

                    // 4. Audit log
                    DB::table('order_status_history')->insert([
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'cancelled',
                        'reason' => 'checkout_timeout',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
            } catch (\Exception $e) {
                Log::error("Failed to cleanup expired reservation", [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("Cleanup expired stock reservations completed", [
            'orders_processed' => $expiredOrders->count(),
            'items_released' => $releasedCount,
            'expiration_minutes' => $this->expirationMinutes,
        ]);
    }
}

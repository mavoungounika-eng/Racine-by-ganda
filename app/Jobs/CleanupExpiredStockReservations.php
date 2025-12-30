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

        // Trouver les commandes expirées avec réservations
        $expiredOrders = Order::where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where('created_at', '<=', $expiredAt)
            ->with('items')
            ->get();

        if ($expiredOrders->isEmpty()) {
            Log::info('No expired stock reservations to cleanup');
            return;
        }

        $releasedCount = 0;

        foreach ($expiredOrders as $order) {
            try {
                DB::transaction(function () use ($order, &$releasedCount) {
                    foreach ($order->items as $item) {
                        $product = Product::lockForUpdate()->find($item->product_id);
                        
                        if (!$product) {
                            Log::warning("Product not found for cleanup", [
                                'product_id' => $item->product_id,
                                'order_id' => $order->id,
                            ]);
                            continue;
                        }

                        // Libérer la réservation
                        if ($product->stock_reserved >= $item->quantity) {
                            $product->decrement('stock_reserved', $item->quantity);
                            $releasedCount++;
                            
                            Log::info("Stock reservation released (expired)", [
                                'product_id' => $product->id,
                                'quantity' => $item->quantity,
                                'order_id' => $order->id,
                                'order_age_minutes' => now()->diffInMinutes($order->created_at),
                            ]);
                        } else {
                            Log::warning("Stock reserved mismatch during cleanup", [
                                'product_id' => $product->id,
                                'stock_reserved' => $product->stock_reserved,
                                'quantity_to_release' => $item->quantity,
                                'order_id' => $order->id,
                            ]);
                        }
                    }

                    // Marquer la commande comme expirée
                    $order->update([
                        'status' => 'cancelled',
                        'payment_status' => 'failed',
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

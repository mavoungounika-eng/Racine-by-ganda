<?php

namespace App\Listeners;

use App\Events\CheckoutStarted;
use App\Events\OrderPlaced;
use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Events\ProductAddedToCart;
use App\Models\FunnelEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Listener générique pour enregistrer les événements du funnel d'achat
 * 
 * Phase 3 : Monitoring simple du tunnel d'achat
 * 
 * Enregistre les événements dans :
 * - La table `funnel_events` (pour analyse future)
 * - Le fichier de log `storage/logs/funnel.log` (pour debugging)
 */
class LogFunnelEvent
{
    /**
     * Handle ProductAddedToCart event
     */
    public function handleProductAddedToCart(ProductAddedToCart $event): void
    {
        $this->logEvent('product_added_to_cart', [
            'user_id' => $event->userId,
            'product_id' => $event->product->id,
            'product_title' => $event->product->title,
            'quantity' => $event->quantity,
            'price' => $event->product->price,
        ]);
    }

    /**
     * Handle CheckoutStarted event
     */
    public function handleCheckoutStarted(CheckoutStarted $event): void
    {
        $this->logEvent('checkout_started', [
            'user_id' => $event->userId,
            'cart_items_count' => $event->cartItemsCount,
            'cart_total' => $event->cartTotal,
        ]);
    }

    /**
     * Handle OrderPlaced event
     */
    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $this->logEvent('order_placed', [
            'user_id' => $event->userId,
            'order_id' => $event->order->id,
            'payment_method' => $event->paymentMethod,
            'total_amount' => $event->totalAmount,
        ]);
    }

    /**
     * Handle PaymentCompleted event
     */
    public function handlePaymentCompleted(PaymentCompleted $event): void
    {
        $this->logEvent('payment_completed', [
            'user_id' => $event->userId,
            'order_id' => $event->order->id,
            'payment_id' => $event->payment->id,
            'payment_method' => $event->paymentMethod,
            'amount' => $event->amount,
        ]);
    }

    /**
     * Handle PaymentFailed event
     */
    public function handlePaymentFailed(PaymentFailed $event): void
    {
        $this->logEvent('payment_failed', [
            'user_id' => $event->userId,
            'order_id' => $event->order->id,
            'payment_method' => $event->paymentMethod,
            'reason' => $event->reason,
        ]);
    }

    /**
     * Enregistrer l'événement dans la DB et le log
     */
    protected function logEvent(string $eventType, array $metadata): void
    {
        try {
            // Enregistrer dans la base de données
            FunnelEvent::create([
                'event_type' => $eventType,
                'user_id' => $metadata['user_id'] ?? null,
                'order_id' => $metadata['order_id'] ?? null,
                'product_id' => $metadata['product_id'] ?? null,
                'metadata' => $metadata,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'occurred_at' => now(),
            ]);

            // Logger dans le fichier dédié
            Log::channel('funnel')->info("Funnel Event: {$eventType}", $metadata);
        } catch (\Throwable $e) {
            // Ne pas faire échouer le processus principal si le logging échoue
            Log::error("Failed to log funnel event: {$eventType}", [
                'error' => $e->getMessage(),
                'metadata' => $metadata,
            ]);
        }
    }
}

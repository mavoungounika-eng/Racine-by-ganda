<?php

namespace App\Listeners;

use App\Events\OrphanedPaymentDetected;
use App\Models\PaymentAuditLog;
use Illuminate\Support\Facades\Log;

class NotifyOrphanedPaymentDetected
{
    public function handle(OrphanedPaymentDetected $event): void
    {
        $order = $event->order;
        $payment = $event->payment;

        Log::warning('Orphaned payment detected - logged for manual reconciliation', [
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment_id' => $payment->id,
            'payment_status' => $payment->status ?? null,
            'stripe_id' => $payment->stripe_id ?? null,
        ]);

        PaymentAuditLog::create([
            'user_id' => $order->user_id,
            'action' => 'orphaned_payment_detected',
            'target_type' => 'Order',
            'target_id' => $order->id,
            'diff' => [
                'order_status' => $order->status,
                'payment_id' => $payment->id,
                'payment_status' => $payment->status ?? null,
                'stripe_id' => $payment->stripe_id ?? null,
            ],
            'reason' => 'Payment received for terminal order status. Manual reconciliation required.',
            'ip_address' => null,
            'user_agent' => null,
        ]);
    }
}

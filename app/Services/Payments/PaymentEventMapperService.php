<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour mapper les événements Stripe/Monetbil vers les statuts
 * 
 * Source of truth : payment_transactions + orders
 */
class PaymentEventMapperService
{
    /**
     * Mapper un événement Stripe vers le statut de transaction
     *
     * @param string $eventType Type d'événement Stripe
     * @return string|null Statut standardisé ou null si événement ignoré
     */
    public function mapStripeEventToStatus(string $eventType): ?string
    {
        return match ($eventType) {
            'payment_intent.succeeded',
            'checkout.session.completed' => PaymentStatus::SUCCEEDED->value,
            
            'payment_intent.payment_failed',
            'charge.failed' => PaymentStatus::FAILED->value,
            
            'payment_intent.canceled',
            'checkout.session.expired' => PaymentStatus::CANCELED->value,
            
            'payment_intent.processing',
            'charge.pending' => PaymentStatus::PROCESSING->value,
            
            'charge.refunded',
            'refund.created' => PaymentStatus::REFUNDED->value,
            
            default => null, // Événement ignoré
        };
    }

    /**
     * Mapper un événement Monetbil vers le statut de transaction
     *
     * @param array $payload Payload Monetbil
     * @return string|null Statut standardisé ou null si événement ignoré
     */
    public function mapMonetbilEventToStatus(array $payload): ?string
    {
        $status = $payload['status'] ?? $payload['transaction_status'] ?? null;

        return match (strtolower($status ?? '')) {
            'success',
            'successful',
            'completed' => PaymentStatus::SUCCEEDED->value,
            
            'failed',
            'failure',
            'error' => PaymentStatus::FAILED->value,
            
            'pending',
            'processing' => PaymentStatus::PROCESSING->value,
            
            'cancelled',
            'canceled' => PaymentStatus::CANCELED->value,
            
            default => null, // Événement ignoré
        };
    }

    /**
     * Mettre à jour le Payment et la commande selon le statut (v1.1 - source of truth)
     *
     * @param Payment $payment
     * @param string $newStatus Statut standardisé (succeeded, failed, refunded, processing)
     * @return void
     * @throws \InvalidArgumentException Si newStatus n'est pas reconnu
     */
    public function updatePaymentAndOrder(Payment $payment, string $newStatus): void
    {
        DB::transaction(function () use ($payment, $newStatus) {
            // Lock Payment pour éviter double update concurrent
            $lockedPayment = Payment::lockForUpdate()->find($payment->id);
            
            if (!$lockedPayment) {
                Log::debug('PaymentEventMapperService: Payment not found after lock', [
                    'payment_id' => $payment->id,
                ]);
                return;
            }

            // IDEMPOTENCE : Si payment.status est déjà final et compatible, no-op
            $finalStatuses = ['paid', 'failed', 'refunded'];
            if (in_array($lockedPayment->status, $finalStatuses)) {
                $statusMap = [
                    'succeeded' => 'paid',
                    'failed' => 'failed',
                    'refunded' => 'refunded',
                ];
                
                $expectedPaymentStatus = $statusMap[$newStatus] ?? null;
                if ($expectedPaymentStatus && $lockedPayment->status === $expectedPaymentStatus) {
                    Log::debug('PaymentEventMapperService: Payment already in final status (idempotence)', [
                        'payment_id' => $lockedPayment->id,
                        'current_status' => $lockedPayment->status,
                        'new_status' => $newStatus,
                    ]);
                    return;
                }
            }

            // Mapper newStatus (v1.1) -> payment.status (boutique)
            $paymentStatusMap = [
                'succeeded' => 'paid',
                'failed' => 'failed',
                'refunded' => 'refunded',
                'processing' => 'processing',
            ];

            if (!isset($paymentStatusMap[$newStatus])) {
                throw new \InvalidArgumentException("Unknown payment status: {$newStatus}");
            }

            $newPaymentStatus = $paymentStatusMap[$newStatus];

            // Préparer les données de mise à jour
            $updateData = ['status' => $newPaymentStatus];

            // Mettre à jour paid_at (paid_at = now() seulement si paid et paid_at null)
            if ($newPaymentStatus === 'paid' && empty($lockedPayment->paid_at)) {
                $updateData['paid_at'] = now();
            }

            // Mettre à jour le Payment
            $lockedPayment->update($updateData);

            Log::debug('PaymentEventMapperService: Payment status updated', [
                'payment_id' => $lockedPayment->id,
                'old_status' => $payment->status,
                'new_status' => $newPaymentStatus,
            ]);

            // Récupérer l'Order via payment.order_id avec lockForUpdate()
            if (!$lockedPayment->order_id) {
                Log::debug('PaymentEventMapperService: Payment has no order_id', [
                    'payment_id' => $lockedPayment->id,
                ]);
                return;
            }

            $order = Order::lockForUpdate()->find($lockedPayment->order_id);
            
            if (!$order) {
                Log::debug('PaymentEventMapperService: Order not found', [
                    'payment_id' => $lockedPayment->id,
                    'order_id' => $lockedPayment->order_id,
                ]);
                return;
            }

            // Protection contre downgrade : ne pas modifier si Order déjà paid et qu'on reçoit failed/refunded incohérent
            if ($order->payment_status === 'paid' && in_array($newPaymentStatus, ['failed', 'refunded'])) {
                // Vérifier si c'est vraiment un downgrade ou un refund légitime
                if ($newPaymentStatus === 'refunded') {
                    // Refund est acceptable même si Order est paid
                    $orderPaymentStatus = 'refunded';
                    $orderStatus = 'cancelled';
                } else {
                    // Failed sur un Order déjà paid : ne pas downgrader
                    Log::debug('PaymentEventMapperService: Skipping downgrade from paid to failed', [
                        'payment_id' => $lockedPayment->id,
                        'order_id' => $order->id,
                        'order_payment_status' => $order->payment_status,
                        'new_payment_status' => $newPaymentStatus,
                    ]);
                    return;
                }
            } else {
                // Mapper payment.status -> order.payment_status et order.status
                $orderStatusMap = [
                    'paid' => ['payment_status' => 'paid', 'status' => 'processing'],
                    'failed' => ['payment_status' => 'failed', 'status' => 'pending'],
                    'refunded' => ['payment_status' => 'refunded', 'status' => 'cancelled'],
                    'processing' => ['payment_status' => 'pending', 'status' => 'pending'],
                ];

                if (!isset($orderStatusMap[$newPaymentStatus])) {
                    Log::debug('PaymentEventMapperService: Unknown payment status for order mapping', [
                        'payment_id' => $lockedPayment->id,
                        'order_id' => $order->id,
                        'payment_status' => $newPaymentStatus,
                    ]);
                    return;
                }

                $orderPaymentStatus = $orderStatusMap[$newPaymentStatus]['payment_status'];
                $orderStatus = $orderStatusMap[$newPaymentStatus]['status'];
            }

            // Mettre à jour l'Order
            $order->update([
                'payment_status' => $orderPaymentStatus,
                'status' => $orderStatus,
            ]);

            Log::debug('PaymentEventMapperService: Order status updated', [
                'payment_id' => $lockedPayment->id,
                'order_id' => $order->id,
                'order_payment_status' => $orderPaymentStatus,
                'order_status' => $orderStatus,
            ]);
        });
    }

    /**
     * Mettre à jour la transaction et la commande selon le statut
     *
     * @deprecated Utiliser updatePaymentAndOrder() à la place. Cette méthode sera supprimée dans une future version.
     * @param PaymentTransaction $transaction
     * @param string $newStatus Statut standardisé
     * @return void
     */
    public function updateTransactionAndOrder(PaymentTransaction $transaction, string $newStatus): void
    {
        \DB::transaction(function () use ($transaction, $newStatus) {
            // Mettre à jour la transaction (source of truth)
            $transaction->update(['status' => $newStatus]);

            // Mettre à jour la commande si liée
            if ($transaction->order_id) {
                $order = Order::lockForUpdate()->find($transaction->order_id);
                
                if ($order) {
                    $orderStatus = $this->mapPaymentStatusToOrderStatus($newStatus);
                    $orderPaymentStatus = $this->mapPaymentStatusToOrderPaymentStatus($newStatus);

                    $order->update([
                        'status' => $orderStatus,
                        'payment_status' => $orderPaymentStatus,
                    ]);
                }
            }
        });
    }

    /**
     * Mapper le statut de paiement vers le statut de commande
     *
     * @param string $paymentStatus
     * @return string
     */
    private function mapPaymentStatusToOrderStatus(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            PaymentStatus::SUCCEEDED->value => 'processing',
            PaymentStatus::FAILED->value => 'pending',
            PaymentStatus::CANCELED->value => 'cancelled',
            PaymentStatus::REFUNDED->value => 'refunded',
            default => 'pending',
        };
    }

    /**
     * Mapper le statut de paiement vers le statut de paiement de commande
     *
     * @param string $paymentStatus
     * @return string
     */
    private function mapPaymentStatusToOrderPaymentStatus(string $paymentStatus): string
    {
        return match ($paymentStatus) {
            PaymentStatus::SUCCEEDED->value => 'paid',
            PaymentStatus::FAILED->value => 'failed',
            PaymentStatus::CANCELED->value => 'cancelled',
            PaymentStatus::REFUNDED->value => 'refunded',
            default => 'pending',
        };
    }
}



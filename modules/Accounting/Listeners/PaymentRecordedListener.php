<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Events\PaymentRecorded;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class PaymentRecordedListener implements ShouldQueue
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
    public function handle(PaymentRecorded $event): void
    {
        $order = $event->order;

        // Vérifier que le paiement est confirmé
        if ($order->payment_status !== 'paid') {
            Log::info('PaymentRecordedListener: Payment not confirmed, skipping accounting entry', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
            ]);
            return;
        }

        try {
            // Déterminer le type d'écriture selon la méthode de paiement et le créateur
            if ($order->creator_id) {
                // Vente marketplace (avec commission)
                $this->createMarketplaceSaleEntry($order);
            } else {
                // Vente boutique RACINE
                $this->createBoutiqueSaleEntry($order);
            }

            Log::info('PaymentRecordedListener: Accounting entry created successfully', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'is_marketplace' => (bool) $order->creator_id,
            ]);
        } catch (LedgerException $e) {
            Log::error('PaymentRecordedListener: Failed to create accounting entry', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Re-throw pour retry (ShouldQueue)
            throw $e;
        }
    }

    /**
     * Créer écriture vente boutique RACINE
     */
    protected function createBoutiqueSaleEntry($order): void
    {
        $debitAccount = $this->getDebitAccountForPaymentMethod($order->payment_method);

        $this->ledgerService->createSaleEntry(
            order: $order,
            journalCode: 'VTE',
            debitAccount: $debitAccount,
            creditAccount: '7011', // Ventes boutique RACINE
            totalTTC: $order->total_amount,
            vatRate: 18.0
        );
    }

    /**
     * Créer écriture vente marketplace (avec commission)
     */
    protected function createMarketplaceSaleEntry($order): void
    {
        $debitAccount = $this->getDebitAccountForPaymentMethod($order->payment_method);

        $this->ledgerService->createMarketplaceSaleEntry(
            order: $order,
            journalCode: 'VTE',
            debitAccount: $debitAccount,
            totalTTC: $order->total_amount,
            commissionRate: 0.15, // 15% commission
            vatRate: 18.0
        );
    }

    /**
     * Obtenir compte débit selon méthode de paiement
     */
    protected function getDebitAccountForPaymentMethod(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card' => '5112',      // Encaissements Stripe (attente)
            'mobile_money' => '5113', // Encaissements Monetbil (attente)
            'cash' => '5700',      // Caisse boutique
            default => '5112',     // Par défaut: Stripe
        };
    }

    /**
     * Handle a job failure.
     */
    public function failed(PaymentRecorded $event, \Throwable $exception): void
    {
        Log::error('PaymentRecordedListener: Job failed permanently', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}

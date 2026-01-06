<?php

namespace App\Listeners;

use App\Events\PosMobilePaymentConfirmed;
use App\Services\Pos\PosFinanceIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for PosMobilePaymentConfirmed event
 * 
 * Creates PosMobilePaymentIntent and commits it to create accounting entry.
 */
class PosMobilePaymentConfirmedListener implements ShouldQueue
{
    public function __construct(
        protected PosFinanceIntegrationService $financeService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PosMobilePaymentConfirmed $event): void
    {
        $payment = $event->payment;

        Log::info('PosMobilePaymentConfirmedListener: Processing mobile payment', [
            'payment_id' => $payment->id,
            'sale_id' => $payment->pos_sale_id,
        ]);

        try {
            // Créer l'intent pour le paiement mobile
            $intent = $this->financeService->createMobilePaymentIntent($payment);

            // Commiter l'intent (créer écriture comptable)
            if ($intent->canProcess()) {
                $entry = $this->financeService->commitIntent($intent);

                Log::info('PosMobilePaymentConfirmedListener: Mobile payment intent committed', [
                    'payment_id' => $payment->id,
                    'intent_id' => $intent->id,
                    'entry_id' => $entry->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PosMobilePaymentConfirmedListener: Failed to process mobile payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function tries(): int
    {
        return 5;
    }
}

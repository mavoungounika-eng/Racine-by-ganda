<?php

namespace App\Listeners;

use App\Events\PosCardPaymentConfirmed;
use App\Services\Pos\PosFinanceIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener for PosCardPaymentConfirmed event
 * 
 * Creates PosCardPaymentIntent and commits it to create accounting entry.
 */
class PosCardPaymentConfirmedListener implements ShouldQueue
{
    public function __construct(
        protected PosFinanceIntegrationService $financeService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PosCardPaymentConfirmed $event): void
    {
        $payment = $event->payment;

        Log::info('PosCardPaymentConfirmedListener: Processing card payment', [
            'payment_id' => $payment->id,
            'sale_id' => $payment->pos_sale_id,
        ]);

        try {
            // Créer l'intent pour le paiement carte
            $intent = $this->financeService->createCardPaymentIntent($payment);

            // Commiter l'intent (créer écriture comptable)
            if ($intent->canProcess()) {
                $entry = $this->financeService->commitIntent($intent);

                Log::info('PosCardPaymentConfirmedListener: Card payment intent committed', [
                    'payment_id' => $payment->id,
                    'intent_id' => $intent->id,
                    'entry_id' => $entry->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PosCardPaymentConfirmedListener: Failed to process card payment', [
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

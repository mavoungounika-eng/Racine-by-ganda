<?php

namespace Modules\Accounting\Listeners;

use Modules\Accounting\Events\PaymentRecorded;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\LedgerException;
use App\Models\FinancialIntent;
use App\Services\Financial\FinancialIntentService;
use App\Services\Financial\AccountingIdempotenceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Listener pour créer les écritures comptables suite à un paiement confirmé.
 * 
 * ARCHITECTURE INTENT-BASED:
 * Ce listener est maintenant NOTIFICATIONNEL, pas DÉCISIONNEL.
 * Il consomme un FinancialIntent existant et le commit.
 * L'event ne crée pas de vérité financière - l'intent le fait.
 * 
 * FLUX:
 * 1. OrderObserver/PaymentController → FinancialIntentService::createPaymentIntent()
 * 2. Event PaymentRecorded dispatched
 * 3. Ce listener → FinancialIntentService::commitIntent()
 * 4. Intent committed → AccountingEntry créée
 * 
 * GARANTIES D'IDEMPOTENCE:
 * 1. Intent avec idempotency_key UNIQUE
 * 2. Vérification status avant commit
 * 3. Retour silencieux si déjà commis
 * 4. Contrainte UNIQUE DB comme filet de sécurité
 */
class PaymentRecordedListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected LedgerService $ledgerService;
    protected FinancialIntentService $intentService;

    /**
     * Nombre de tentatives maximum
     */
    public int $tries = 3;

    /**
     * Backoff entre tentatives (secondes)
     */
    public array $backoff = [10, 30, 60];

    /**
     * Create the event listener.
     */
    public function __construct(
        LedgerService $ledgerService,
        FinancialIntentService $intentService
    ) {
        $this->ledgerService = $ledgerService;
        $this->intentService = $intentService;
    }

    /**
     * Handle the event.
     */
    public function handle(PaymentRecorded $event): void
    {
        $order = $event->order;

        // GUARD 1: Vérifier que le paiement est confirmé
        if ($order->payment_status !== 'paid') {
            Log::info('PaymentRecordedListener: Payment not confirmed, skipping', [
                'order_id' => $order->id,
                'payment_status' => $order->payment_status,
            ]);
            return;
        }

        // ✅ GOVERNANCE C5: Exit immédiat pour commandes créateurs
        // Vérifier AVANT création d'Intent pour éviter pollution table financial_intents
        if ($order->creator_id !== null) {
            Log::info('PaymentRecordedListener: Creator order detected. Exiting immediately (SaaS Pur).', [
                'order_id' => $order->id,
                'creator_id' => $order->creator_id
            ]);
            return; // EXIT TOTAL - pas d'intent, pas de traitement
        }

        // GUARD 2: Chercher ou créer l'intent (Brand orders only)
        $intent = $this->intentService->findByReference('order', $order->id);
        
        if (!$intent) {
            // Migration progressive: créer l'intent s'il n'existe pas
            // À terme, l'intent sera créé en amont (Controller/OrderObserver)
            $intent = $this->intentService->createPaymentIntent($order);
            
            Log::info('PaymentRecordedListener: Intent created on-the-fly (migration mode)', [
                'order_id' => $order->id,
                'intent_id' => $intent->id,
            ]);
        }

        // GUARD 3: Vérifier si déjà commis
        if ($intent->isCommitted()) {
            Log::info('PaymentRecordedListener: Intent already committed (idempotent)', [
                'order_id' => $order->id,
                'intent_id' => $intent->id,
                'accounting_entry_id' => $intent->accounting_entry_id,
            ]);
            return;
        }

        try {
            // Commiter l'intent avec le callback de création d'écriture
            $this->intentService->commitIntent($intent, function (FinancialIntent $intent, LedgerService $ledger) use ($order) {
                // Modèle SaaS Pur : On n'enregistre comptablement que les ventes propres à RACINE.
                // Les ventes créateurs sont traitées via CreatorSaleRecord (Analytique) et non Ledger.
                if ($order->creator_id) {
                    Log::info('PaymentRecordedListener: Creator sale detected. Skipping accounting entry (SaaS Pur).', [
                        'order_id' => $order->id,
                        'creator_id' => $order->creator_id
                    ]);
                    return null; // Pas d'écriture comptable pour les fonds tiers
                }

                return $this->createBoutiqueSaleEntry($order, $ledger);
            });

            Log::info('PaymentRecordedListener: Intent processed', [
                'order_id' => $order->id,
                'intent_id' => $intent->id,
                'is_racine_sale' => !$order->creator_id,
            ]);

        } catch (LedgerException $e) {
            Log::error('PaymentRecordedListener: Failed to commit intent', [
                'order_id' => $order->id,
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e; // Re-throw pour retry
        }
    }

    /**
     * Créer écriture vente boutique RACINE
     */
    protected function createBoutiqueSaleEntry($order, LedgerService $ledger): AccountingEntry
    {
        $debitAccount = $this->getDebitAccountForPaymentMethod($order->payment_method);

        return $ledger->createSaleEntry(
            order: $order,
            journalCode: 'VTE',
            debitAccount: $debitAccount,
            creditAccount: '7011',
            totalTTC: $order->total_amount,
            vatRate: 18.0
        );
    }

    /**
     * Obtenir compte débit selon méthode de paiement
     */
    protected function getDebitAccountForPaymentMethod(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'card' => '5112',
            'mobile_money' => '5113',
            'cash' => '5700',
            default => '5112',
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

<?php

namespace App\Services\Pos;

use App\Models\FinancialIntent;
use App\Models\PosSession;
use App\Models\PosSale;
use App\Models\PosPayment;
use App\Services\Financial\FinancialIntentService;
use Modules\Accounting\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PosFinanceIntegrationService - Bridge POS → Finance via Intent-Based Architecture
 * 
 * RÈGLE ABSOLUE:
 * Le POS ne déclenche JAMAIS PaymentRecorded.
 * Le POS crée des Intents spécifiques qui sont commis par les Listeners.
 * 
 * TYPES D'INTENT:
 * - pos_cash_settlement: Clôture session → toutes ventes cash
 * - pos_card_payment: Confirmation TPE → vente carte
 * - pos_mobile_payment: Callback Monetbil → vente mobile
 */
class PosFinanceIntegrationService
{
    public function __construct(
        protected FinancialIntentService $intentService,
        protected LedgerService $ledgerService
    ) {}

    /**
     * Créer un Intent pour settlement cash (clôture session)
     * 
     * Appelé par PosSessionClosedListener
     * 
     * @param PosSession $session
     * @return FinancialIntent
     */
    public function createCashSettlementIntent(PosSession $session): FinancialIntent
    {
        // Calculer le total cash de la session
        $cashSales = $session->sales()
            ->where('payment_method', 'cash')
            ->whereIn('status', [PosSale::STATUS_PENDING, PosSale::STATUS_FINALIZED])
            ->get();

        $totalCash = $cashSales->sum('total_amount');

        // Générer idempotency key unique pour cette session
        $idempotencyKey = FinancialIntent::generateIdempotencyKey('pos_session', $session->id);

        // Vérifier si intent existe déjà
        $existing = FinancialIntent::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            Log::info('PosFinanceIntegration: Cash settlement intent already exists', [
                'session_id' => $session->id,
                'intent_id' => $existing->id,
            ]);
            return $existing;
        }

        // Créer l'intent
        $intent = FinancialIntent::create([
            'intent_type' => 'pos_cash_settlement',
            'reference_type' => 'pos_session',
            'reference_id' => $session->id,
            'amount' => $totalCash,
            'currency' => 'XAF',
            'status' => FinancialIntent::STATUS_PENDING,
            'idempotency_key' => $idempotencyKey,
            'metadata' => [
                'session_id' => $session->id,
                'machine_id' => $session->machine_id,
                'sales_count' => $cashSales->count(),
                'opening_cash' => $session->opening_cash,
                'closing_cash' => $session->closing_cash,
                'expected_cash' => $session->expected_cash,
                'cash_difference' => $session->cash_difference,
                'closed_at' => $session->closed_at->toIso8601String(),
            ],
            'created_by' => $session->closed_by,
        ]);

        Log::info('PosFinanceIntegration: Cash settlement intent created', [
            'session_id' => $session->id,
            'intent_id' => $intent->id,
            'total_cash' => $totalCash,
            'sales_count' => $cashSales->count(),
        ]);

        return $intent;
    }

    /**
     * Créer un Intent pour paiement carte confirmé
     * 
     * Appelé par PosCardPaymentConfirmedListener
     * 
     * @param PosPayment $payment
     * @return FinancialIntent
     */
    public function createCardPaymentIntent(PosPayment $payment): FinancialIntent
    {
        $sale = $payment->sale;

        // Générer idempotency key
        $idempotencyKey = FinancialIntent::generateIdempotencyKey('pos_payment', $payment->id);

        // Vérifier si intent existe déjà
        $existing = FinancialIntent::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        // Créer l'intent
        $intent = FinancialIntent::create([
            'intent_type' => 'pos_card_payment',
            'reference_type' => 'pos_payment',
            'reference_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => 'XAF',
            'status' => FinancialIntent::STATUS_PENDING,
            'idempotency_key' => $idempotencyKey,
            'metadata' => [
                'payment_id' => $payment->id,
                'sale_id' => $sale->id,
                'order_id' => $sale->order_id,
                'machine_id' => $sale->machine_id,
                'external_reference' => $payment->external_reference,
                'confirmed_at' => $payment->confirmed_at->toIso8601String(),
            ],
            'created_by' => $payment->confirmed_by,
        ]);

        Log::info('PosFinanceIntegration: Card payment intent created', [
            'payment_id' => $payment->id,
            'intent_id' => $intent->id,
        ]);

        return $intent;
    }

    /**
     * Créer un Intent pour paiement mobile confirmé
     * 
     * Appelé par PosMobilePaymentConfirmedListener
     * 
     * @param PosPayment $payment
     * @return FinancialIntent
     */
    public function createMobilePaymentIntent(PosPayment $payment): FinancialIntent
    {
        $sale = $payment->sale;

        // Générer idempotency key
        $idempotencyKey = FinancialIntent::generateIdempotencyKey('pos_payment', $payment->id);

        // Vérifier si intent existe déjà
        $existing = FinancialIntent::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        // Créer l'intent
        $intent = FinancialIntent::create([
            'intent_type' => 'pos_mobile_payment',
            'reference_type' => 'pos_payment',
            'reference_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => 'XAF',
            'status' => FinancialIntent::STATUS_PENDING,
            'idempotency_key' => $idempotencyKey,
            'metadata' => [
                'payment_id' => $payment->id,
                'sale_id' => $sale->id,
                'order_id' => $sale->order_id,
                'machine_id' => $sale->machine_id,
                'external_reference' => $payment->external_reference,
                'provider' => $payment->provider,
                'confirmed_at' => $payment->confirmed_at->toIso8601String(),
            ],
        ]);

        Log::info('PosFinanceIntegration: Mobile payment intent created', [
            'payment_id' => $payment->id,
            'intent_id' => $intent->id,
        ]);

        return $intent;
    }

    /**
     * Commiter un Intent POS et créer l'écriture comptable
     * 
     * @param FinancialIntent $intent
     * @return \Modules\Accounting\Models\AccountingEntry
     */
    public function commitIntent(FinancialIntent $intent)
    {
        return $this->intentService->commitIntent($intent, function ($intent, $ledger) {
            return match ($intent->intent_type) {
                'pos_cash_settlement' => $this->createCashSettlementEntry($intent, $ledger),
                'pos_card_payment' => $this->createCardPaymentEntry($intent, $ledger),
                'pos_mobile_payment' => $this->createMobilePaymentEntry($intent, $ledger),
                default => throw new \Exception("Unknown POS intent type: {$intent->intent_type}"),
            };
        });
    }

    /**
     * Créer écriture comptable pour cash settlement
     */
    protected function createCashSettlementEntry(FinancialIntent $intent, LedgerService $ledger)
    {
        $session = PosSession::findOrFail($intent->reference_id);
        
        return $ledger->createSaleEntry(
            order: (object) [
                'id' => "SESSION-{$session->id}",
            ],
            journalCode: 'VTE',
            debitAccount: '5700', // Caisse
            creditAccount: '7011', // Ventes marchandises
            totalTTC: (float) $intent->amount,
            vatRate: 18.0
        );
    }

    /**
     * Créer écriture comptable pour card payment
     */
    protected function createCardPaymentEntry(FinancialIntent $intent, LedgerService $ledger)
    {
        $payment = PosPayment::findOrFail($intent->reference_id);
        
        return $ledger->createSaleEntry(
            order: $payment->sale->order,
            journalCode: 'VTE',
            debitAccount: '5112', // Banque - Cartes
            creditAccount: '7011', // Ventes marchandises
            totalTTC: (float) $intent->amount,
            vatRate: 18.0
        );
    }

    /**
     * Créer écriture comptable pour mobile payment
     */
    protected function createMobilePaymentEntry(FinancialIntent $intent, LedgerService $ledger)
    {
        $payment = PosPayment::findOrFail($intent->reference_id);
        
        return $ledger->createSaleEntry(
            order: $payment->sale->order,
            journalCode: 'VTE',
            debitAccount: '5113', // Mobile Money
            creditAccount: '7011', // Ventes marchandises
            totalTTC: (float) $intent->amount,
            vatRate: 18.0
        );
    }
}

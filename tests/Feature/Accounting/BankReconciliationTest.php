<?php

namespace Tests\Feature\Accounting;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Services\BankReconciliationService;
use Modules\Accounting\Services\LedgerService;
use Carbon\Carbon;
use Tests\Traits\SeedsAccounting;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $user;
    protected BankReconciliationService $reconciliationService;
    protected LedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed donnÃ©es comptables via trait explicite
        $this->seedAccounting();

        $this->reconciliationService = app(BankReconciliationService::class);
        $this->ledgerService = app(LedgerService::class);
    }

    /**
     * Helper: CrÃ©er une Ã©criture comptable de paiement en attente
     * Simule l'Ã©criture crÃ©Ã©e par PaymentRecordedListener
     */
    protected function createPendingPaymentEntry(Order $order): AccountingEntry
    {
        $debitAccount = match ($order->payment_method) {
            'card' => '5112',           // Encaissements Stripe (attente)
            'mobile_money' => '5113',   // Encaissements Monetbil (attente)
            'cash' => '5700',           // Caisse
            default => '5112',
        };

        $journal = Journal::where('code', 'VTE')->firstOrFail();
        $fiscalYear = $this->ledgerService->getCurrentFiscalYear();

        $totalTTC = $order->total_amount;
        $vatRate = 18.0;
        $amountHT = $totalTTC / (1 + $vatRate / 100);
        $vatAmount = $totalTTC - $amountHT;

        $entry = $this->ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => "Vente commande #{$order->id}",
            'reference_type' => 'order',
            'reference_id' => $order->id,
        ]);

        $this->ledgerService->addLine($entry, $debitAccount, $totalTTC, 0, "Encaissement commande #{$order->id}");
        $this->ledgerService->addLine($entry, '7011', 0, $amountHT, "Vente HT");
        $this->ledgerService->addLine($entry, '4421', 0, $vatAmount, "TVA collectÃ©e {$vatRate}%");

        $this->ledgerService->postEntry($entry);

        return $entry;
    }
    #[Test]
    public function it_reconciles_stripe_payout()
    {
        // CrÃ©er vente Stripe (compte attente 5112)
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Simuler l'Ã©criture comptable crÃ©Ã©e par PaymentRecordedListener
        $initialEntry = $this->createPendingPaymentEntry($order);
        $this->assertNotNull($initialEntry);

        // VÃ©rifier solde compte attente
        $pendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(118.00, $pendingAmount);

        // Simuler payout Stripe
        $reconciliation = $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_123',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // VÃ©rifier rapprochement crÃ©Ã©
        $this->assertEquals('reconciled', $reconciliation->status);
        $this->assertEquals('5211', $reconciliation->bank_account_code);
        $this->assertEquals('po_test_123', $reconciliation->transaction_reference);
        $this->assertEquals(118.00, $reconciliation->amount);

        // VÃ©rifier Ã©criture rapprochement
        $entry = $reconciliation->entry;
        $this->assertNotNull($entry);
        $this->assertTrue($entry->is_posted);
        $this->assertEquals('BNQ', $entry->journal->code);

        // VÃ©rifier lignes
        $lines = $entry->lines;
        $this->assertCount(2, $lines);

        // DÃ©bit banque Stripe (5211)
        $debitLine = $lines->where('account_code', '5211')->first();
        $this->assertNotNull($debitLine);
        $this->assertEquals(118.00, $debitLine->debit);
        $this->assertEquals(0, $debitLine->credit);

        // CrÃ©dit compte attente (5112)
        $creditLine = $lines->where('account_code', '5112')->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals(0, $creditLine->debit);
        $this->assertEquals(118.00, $creditLine->credit);

        // VÃ©rifier solde compte attente = 0
        $newPendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(0, $newPendingAmount);
    }
    #[Test]
    public function it_reconciles_monetbil_payout()
    {
        // CrÃ©er vente Monetbil (compte attente 5113)
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'mobile_money',
            'payment_status' => 'paid',
        ]);

        // Simuler l'Ã©criture comptable crÃ©Ã©e par PaymentRecordedListener
        $this->createPendingPaymentEntry($order);

        // VÃ©rifier solde compte attente
        $pendingAmount = $this->reconciliationService->getPendingMonetbilAmount();
        $this->assertEquals(59.00, $pendingAmount);

        // Simuler payout Monetbil
        $reconciliation = $this->reconciliationService->reconcileMonetbilPayout(
            payoutId: 'mb_payout_456',
            amount: 59.00,
            arrivalDate: Carbon::now()
        );

        // VÃ©rifier rapprochement crÃ©Ã©
        $this->assertEquals('reconciled', $reconciliation->status);
        $this->assertEquals('5212', $reconciliation->bank_account_code);

        // VÃ©rifier Ã©criture
        $entry = $reconciliation->entry;
        $lines = $entry->lines;

        // DÃ©bit banque Monetbil (5212)
        $debitLine = $lines->where('account_code', '5212')->first();
        $this->assertEquals(59.00, $debitLine->debit);

        // CrÃ©dit compte attente (5113)
        $creditLine = $lines->where('account_code', '5113')->first();
        $this->assertEquals(59.00, $creditLine->credit);

        // VÃ©rifier solde compte attente = 0
        $newPendingAmount = $this->reconciliationService->getPendingMonetbilAmount();
        $this->assertEquals(0, $newPendingAmount);
    }
    #[Test]
    public function it_prevents_duplicate_reconciliation()
    {
        // CrÃ©er vente
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Simuler l'Ã©criture comptable crÃ©Ã©e par PaymentRecordedListener
        $this->createPendingPaymentEntry($order);

        // Premier rapprochement
        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_789',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // Tentative de double rapprochement
        $this->expectException(\Modules\Accounting\Exceptions\LedgerException::class);
        $this->expectExceptionMessage('rapproch');

        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_789',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );
    }
    #[Test]
    public function it_validates_sufficient_pending_amount()
    {
        // CrÃ©er vente de 118 â‚¬
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Simuler l'Ã©criture comptable crÃ©Ã©e par PaymentRecordedListener
        $this->createPendingPaymentEntry($order);

        // Tentative de rapprocher 200 â‚¬ (> 118 â‚¬)
        $this->expectException(\Modules\Accounting\Exceptions\LedgerException::class);
        $this->expectExceptionMessage('encaissements en attente');

        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_999',
            amount: 200.00,
            arrivalDate: Carbon::now()
        );
    }
    #[Test]
    public function it_calculates_pending_amounts_correctly()
    {
        // CrÃ©er 3 ventes Stripe
        for ($i = 0; $i < 3; $i++) {
            $order = Order::factory()->create([
                'user_id' => $this->user->id,
                'total_amount' => 118.00,
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);
            // Simuler l'Ã©criture comptable crÃ©Ã©e par PaymentRecordedListener
            $this->createPendingPaymentEntry($order);
        }

        // VÃ©rifier solde total
        $pendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(354.00, $pendingAmount); // 118 Ã— 3

        // Rapprocher 118 â‚¬
        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_partial_1',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // VÃ©rifier solde restant
        $newPendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(236.00, $newPendingAmount); // 354 - 118
    }
    #[Test]
    public function it_retrieves_reconciled_reconciliations()
    {
        // CrÃ©er vente
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Simuler l'Ã©criture comptable crÃ©Ã©e par PaymentRecordedListener
        $this->createPendingPaymentEntry($order);

        // CrÃ©er rapprochement
        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_list',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // RÃ©cupÃ©rer rapprochements validÃ©s
        $reconciliations = $this->reconciliationService->getReconciledReconciliations();

        $this->assertCount(1, $reconciliations);
        $this->assertEquals('po_test_list', $reconciliations->first()->transaction_reference);
    }
}

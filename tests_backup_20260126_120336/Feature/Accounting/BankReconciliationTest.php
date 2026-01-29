<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\BankReconciliation;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Services\BankReconciliationService;
use Carbon\Carbon;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected BankReconciliationService $reconciliationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        $this->reconciliationService = app(BankReconciliationService::class);
    }

    /** @test */
    public function it_reconciles_stripe_payout()
    {
        // Créer vente Stripe (compte attente 5112)
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Vérifier écriture initiale créée
        $initialEntry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();
        $this->assertNotNull($initialEntry);

        // Vérifier solde compte attente
        $pendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(118.00, $pendingAmount);

        // Simuler payout Stripe
        $reconciliation = $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_123',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // Vérifier rapprochement créé
        $this->assertEquals('reconciled', $reconciliation->status);
        $this->assertEquals('5211', $reconciliation->bank_account_code);
        $this->assertEquals('po_test_123', $reconciliation->transaction_reference);
        $this->assertEquals(118.00, $reconciliation->amount);

        // Vérifier écriture rapprochement
        $entry = $reconciliation->entry;
        $this->assertNotNull($entry);
        $this->assertTrue($entry->is_posted);
        $this->assertEquals('BNQ', $entry->journal->code);

        // Vérifier lignes
        $lines = $entry->lines;
        $this->assertCount(2, $lines);

        // Débit banque Stripe (5211)
        $debitLine = $lines->where('account_code', '5211')->first();
        $this->assertNotNull($debitLine);
        $this->assertEquals(118.00, $debitLine->debit);
        $this->assertEquals(0, $debitLine->credit);

        // Crédit compte attente (5112)
        $creditLine = $lines->where('account_code', '5112')->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals(0, $creditLine->debit);
        $this->assertEquals(118.00, $creditLine->credit);

        // Vérifier solde compte attente = 0
        $newPendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(0, $newPendingAmount);
    }

    /** @test */
    public function it_reconciles_monetbil_payout()
    {
        // Créer vente Monetbil (compte attente 5113)
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'mobile_money',
            'payment_status' => 'paid',
        ]);

        // Vérifier solde compte attente
        $pendingAmount = $this->reconciliationService->getPendingMonetbilAmount();
        $this->assertEquals(59.00, $pendingAmount);

        // Simuler payout Monetbil
        $reconciliation = $this->reconciliationService->reconcileMonetbilPayout(
            payoutId: 'mb_payout_456',
            amount: 59.00,
            arrivalDate: Carbon::now()
        );

        // Vérifier rapprochement créé
        $this->assertEquals('reconciled', $reconciliation->status);
        $this->assertEquals('5212', $reconciliation->bank_account_code);

        // Vérifier écriture
        $entry = $reconciliation->entry;
        $lines = $entry->lines;

        // Débit banque Monetbil (5212)
        $debitLine = $lines->where('account_code', '5212')->first();
        $this->assertEquals(59.00, $debitLine->debit);

        // Crédit compte attente (5113)
        $creditLine = $lines->where('account_code', '5113')->first();
        $this->assertEquals(59.00, $creditLine->credit);

        // Vérifier solde compte attente = 0
        $newPendingAmount = $this->reconciliationService->getPendingMonetbilAmount();
        $this->assertEquals(0, $newPendingAmount);
    }

    /** @test */
    public function it_prevents_duplicate_reconciliation()
    {
        // Créer vente
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Premier rapprochement
        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_789',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // Tentative de double rapprochement
        $this->expectException(\Modules\Accounting\Exceptions\LedgerException::class);
        $this->expectExceptionMessage('déjà rapproché');

        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_789',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );
    }

    /** @test */
    public function it_validates_sufficient_pending_amount()
    {
        // Créer vente de 118 €
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Tentative de rapprocher 200 € (> 118 €)
        $this->expectException(\Modules\Accounting\Exceptions\LedgerException::class);
        $this->expectExceptionMessage('supérieur aux encaissements en attente');

        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_999',
            amount: 200.00,
            arrivalDate: Carbon::now()
        );
    }

    /** @test */
    public function it_calculates_pending_amounts_correctly()
    {
        // Créer 3 ventes Stripe
        for ($i = 0; $i < 3; $i++) {
            Order::factory()->create([
                'user_id' => $this->user->id,
                'total_amount' => 118.00,
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);
        }

        // Vérifier solde total
        $pendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(354.00, $pendingAmount); // 118 × 3

        // Rapprocher 118 €
        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_partial_1',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // Vérifier solde restant
        $newPendingAmount = $this->reconciliationService->getPendingStripeAmount();
        $this->assertEquals(236.00, $newPendingAmount); // 354 - 118
    }

    /** @test */
    public function it_retrieves_reconciled_reconciliations()
    {
        // Créer vente
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Créer rapprochement
        $this->reconciliationService->reconcileStripePayout(
            payoutId: 'po_test_list',
            amount: 118.00,
            arrivalDate: Carbon::now()
        );

        // Récupérer rapprochements validés
        $reconciliations = $this->reconciliationService->getReconciledReconciliations();

        $this->assertCount(1, $reconciliations);
        $this->assertEquals('po_test_list', $reconciliations->first()->transaction_reference);
    }
}

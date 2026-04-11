<?php

namespace Tests\Feature\Accounting;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use App\Models\CreatorPayout;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\AccountingEntryLine;
use Modules\Accounting\Events\CreatorPayoutProcessed;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Group;

/**
 * âš ï¸ TESTS OBSOLÃˆTES â€” ARCHITECTURE SAAS PUR
 * 
 * Ces tests supposent que RACINE comptabilise les ventes des crÃ©ateurs (compte 4671).
 * Cette architecture a Ã©tÃ© abandonnÃ©e au profit du modÃ¨le "SaaS Pur" oÃ¹ :
 * 
 * 1. RACINE ne comptabilise PAS les fonds des crÃ©ateurs dans son Ledger
 * 2. Les ventes crÃ©ateurs sont trackÃ©es via CreatorSaleRecord (analytique)
 * 3. Les payouts crÃ©ateurs sont des transferts directs, pas des Ã©critures comptables RACINE
 * 
 * @see LedgerService::createEntry() â€” Guard "SÃ‰CURITÃ‰ SAAS PUR"
 * @see PaymentRecordedListener::handle() â€” Skip des ordres crÃ©ateurs
 * @see ChartOfAccountsSeeder â€” Compte 4671 commentÃ© (DÃ‰SACTIVÃ‰)
 * 
 * Ces tests sont conservÃ©s comme documentation historique mais skippÃ©s.
 */
#[Group('skip')]
class CreatorPayoutAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        // Skip tous les tests de cette classe
        $this->markTestSkipped('Architecture SaaS Pur: RACINE ne comptabilise pas les fonds crÃ©ateurs. Voir docblock de la classe.');
    }
    #[Test]
    public function it_creates_accounting_entry_for_creator_payout()
    {
        // CrÃ©er vente marketplace (dette crÃ©ateur 85 â‚¬)
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $this->creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // VÃ©rifier Ã©criture vente crÃ©Ã©e
        $saleEntry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();
        $this->assertNotNull($saleEntry);

        // VÃ©rifier dette crÃ©ateur (compte 4671)
        $creatorDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(85.00, $creatorDebt);

        // CrÃ©er payout
        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'paid',
            'stripe_transfer_id' => 'tr_test_123',
            'paid_at' => now(),
        ]);

        // Dispatch Ã©vÃ©nement
        event(new CreatorPayoutProcessed($payout));

        // VÃ©rifier Ã©criture payout crÃ©Ã©e
        $payoutEntry = AccountingEntry::where('reference_type', 'creator_payout')
            ->where('reference_id', $payout->id)
            ->first();

        $this->assertNotNull($payoutEntry);
        $this->assertTrue($payoutEntry->is_posted);
        $this->assertEquals('BNQ', $payoutEntry->journal->code);

        // VÃ©rifier lignes
        $lines = $payoutEntry->lines;
        $this->assertCount(2, $lines);

        // Ligne 1: DÃ©bit dette crÃ©ateur (4671)
        $debitLine = $lines->where('account_code', '4671')->first();
        $this->assertNotNull($debitLine);
        $this->assertEquals(85.00, $debitLine->debit);
        $this->assertEquals(0, $debitLine->credit);

        // Ligne 2: CrÃ©dit banque Stripe (5211)
        $creditLine = $lines->where('account_code', '5211')->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals(0, $creditLine->debit);
        $this->assertEquals(85.00, $creditLine->credit);

        // VÃ©rifier dette crÃ©ateur soldÃ©e
        $newDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(0, $newDebt);
    }
    #[Test]
    public function it_handles_multiple_sales_before_payout()
    {
        // CrÃ©er 3 ventes marketplace (3 Ã— 85 = 255 â‚¬ de dette)
        for ($i = 0; $i < 3; $i++) {
            Order::factory()->create([
                'user_id' => $this->user->id,
                'creator_id' => $this->creator->id,
                'total_amount' => 118.00,
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);
        }

        // VÃ©rifier dette totale
        $totalDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(255.00, $totalDebt);

        // Payout partiel de 100 â‚¬
        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 100.00,
            'status' => 'paid',
        ]);

        event(new CreatorPayoutProcessed($payout));

        // VÃ©rifier dette restante
        $remainingDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(155.00, $remainingDebt); // 255 - 100
    }
    #[Test]
    public function it_does_not_create_entry_if_payout_not_confirmed()
    {
        // CrÃ©er vente marketplace
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $this->creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // CrÃ©er payout en attente
        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'pending', // Pas 'paid'
        ]);

        event(new CreatorPayoutProcessed($payout));

        // VÃ©rifier pas d'Ã©criture crÃ©Ã©e
        $payoutEntry = AccountingEntry::where('reference_type', 'creator_payout')
            ->where('reference_id', $payout->id)
            ->first();

        $this->assertNull($payoutEntry);
    }
    #[Test]
    public function it_dispatches_creator_payout_event()
    {
        Event::fake([CreatorPayoutProcessed::class]);

        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'paid',
        ]);

        event(new CreatorPayoutProcessed($payout));

        Event::assertDispatched(CreatorPayoutProcessed::class, function ($event) use ($payout) {
            return $event->payout->id === $payout->id;
        });
    }
    #[Test]
    public function it_tracks_multiple_creators_separately()
    {
        $creator2 = User::factory()->create(['role' => 'createur']);

        // Vente crÃ©ateur 1
        Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $this->creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Vente crÃ©ateur 2
        Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $creator2->id,
            'total_amount' => 236.00, // 200 HT â†’ 170 dette crÃ©ateur
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // VÃ©rifier dettes sÃ©parÃ©es
        $debt1 = $this->getCreatorDebt($this->creator->id);
        $debt2 = $this->getCreatorDebt($creator2->id);

        $this->assertEquals(85.00, $debt1);
        $this->assertEquals(170.00, $debt2);

        // Payout crÃ©ateur 1
        $payout1 = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'paid',
        ]);

        event(new CreatorPayoutProcessed($payout1));

        // VÃ©rifier dette crÃ©ateur 1 soldÃ©e, dette crÃ©ateur 2 inchangÃ©e
        $this->assertEquals(0, $this->getCreatorDebt($this->creator->id));
        $this->assertEquals(170.00, $this->getCreatorDebt($creator2->id));
    }

    /**
     * Calculer dette crÃ©ateur (solde compte 4671 pour ce crÃ©ateur)
     */
    protected function getCreatorDebt(int $creatorId): float
    {
        // CrÃ©dits (ventes marketplace)
        $credits = AccountingEntryLine::where('account_code', '4671')
            ->whereHas('entry', function ($q) use ($creatorId) {
                $q->posted()
                  ->where('reference_type', 'order')
                  ->whereHas('reference', fn($q2) => $q2->where('creator_id', $creatorId));
            })
            ->sum('credit');

        // DÃ©bits (payouts)
        $debits = AccountingEntryLine::where('account_code', '4671')
            ->whereHas('entry', function ($q) use ($creatorId) {
                $q->posted()
                  ->where('reference_type', 'creator_payout')
                  ->whereHas('reference', fn($q2) => $q2->where('creator_id', $creatorId));
            })
            ->sum('debit');

        return $credits - $debits;
    }
}

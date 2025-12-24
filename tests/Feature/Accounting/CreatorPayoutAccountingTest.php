<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use App\Models\CreatorPayout;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\AccountingEntryLine;
use Modules\Accounting\Events\CreatorPayoutProcessed;
use Illuminate\Support\Facades\Event;

class CreatorPayoutAccountingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->creator = User::factory()->create(['role' => 'creator']);
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);
    }

    /** @test */
    public function it_creates_accounting_entry_for_creator_payout()
    {
        // Créer vente marketplace (dette créateur 85 €)
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $this->creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Vérifier écriture vente créée
        $saleEntry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();
        $this->assertNotNull($saleEntry);

        // Vérifier dette créateur (compte 4671)
        $creatorDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(85.00, $creatorDebt);

        // Créer payout
        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'paid',
            'stripe_transfer_id' => 'tr_test_123',
            'paid_at' => now(),
        ]);

        // Dispatch événement
        event(new CreatorPayoutProcessed($payout));

        // Vérifier écriture payout créée
        $payoutEntry = AccountingEntry::where('reference_type', 'creator_payout')
            ->where('reference_id', $payout->id)
            ->first();

        $this->assertNotNull($payoutEntry);
        $this->assertTrue($payoutEntry->is_posted);
        $this->assertEquals('BNQ', $payoutEntry->journal->code);

        // Vérifier lignes
        $lines = $payoutEntry->lines;
        $this->assertCount(2, $lines);

        // Ligne 1: Débit dette créateur (4671)
        $debitLine = $lines->where('account_code', '4671')->first();
        $this->assertNotNull($debitLine);
        $this->assertEquals(85.00, $debitLine->debit);
        $this->assertEquals(0, $debitLine->credit);

        // Ligne 2: Crédit banque Stripe (5211)
        $creditLine = $lines->where('account_code', '5211')->first();
        $this->assertNotNull($creditLine);
        $this->assertEquals(0, $creditLine->debit);
        $this->assertEquals(85.00, $creditLine->credit);

        // Vérifier dette créateur soldée
        $newDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(0, $newDebt);
    }

    /** @test */
    public function it_handles_multiple_sales_before_payout()
    {
        // Créer 3 ventes marketplace (3 × 85 = 255 € de dette)
        for ($i = 0; $i < 3; $i++) {
            Order::factory()->create([
                'user_id' => $this->user->id,
                'creator_id' => $this->creator->id,
                'total_amount' => 118.00,
                'payment_method' => 'card',
                'payment_status' => 'paid',
            ]);
        }

        // Vérifier dette totale
        $totalDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(255.00, $totalDebt);

        // Payout partiel de 100 €
        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 100.00,
            'status' => 'paid',
        ]);

        event(new CreatorPayoutProcessed($payout));

        // Vérifier dette restante
        $remainingDebt = $this->getCreatorDebt($this->creator->id);
        $this->assertEquals(155.00, $remainingDebt); // 255 - 100
    }

    /** @test */
    public function it_does_not_create_entry_if_payout_not_confirmed()
    {
        // Créer vente marketplace
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $this->creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Créer payout en attente
        $payout = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'pending', // Pas 'paid'
        ]);

        event(new CreatorPayoutProcessed($payout));

        // Vérifier pas d'écriture créée
        $payoutEntry = AccountingEntry::where('reference_type', 'creator_payout')
            ->where('reference_id', $payout->id)
            ->first();

        $this->assertNull($payoutEntry);
    }

    /** @test */
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

    /** @test */
    public function it_tracks_multiple_creators_separately()
    {
        $creator2 = User::factory()->create(['role' => 'creator']);

        // Vente créateur 1
        Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $this->creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Vente créateur 2
        Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $creator2->id,
            'total_amount' => 236.00, // 200 HT → 170 dette créateur
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        // Vérifier dettes séparées
        $debt1 = $this->getCreatorDebt($this->creator->id);
        $debt2 = $this->getCreatorDebt($creator2->id);

        $this->assertEquals(85.00, $debt1);
        $this->assertEquals(170.00, $debt2);

        // Payout créateur 1
        $payout1 = CreatorPayout::create([
            'creator_id' => $this->creator->id,
            'amount' => 85.00,
            'status' => 'paid',
        ]);

        event(new CreatorPayoutProcessed($payout1));

        // Vérifier dette créateur 1 soldée, dette créateur 2 inchangée
        $this->assertEquals(0, $this->getCreatorDebt($this->creator->id));
        $this->assertEquals(170.00, $this->getCreatorDebt($creator2->id));
    }

    /**
     * Calculer dette créateur (solde compte 4671 pour ce créateur)
     */
    protected function getCreatorDebt(int $creatorId): float
    {
        // Crédits (ventes marketplace)
        $credits = AccountingEntryLine::where('account_code', '4671')
            ->whereHas('entry', function ($q) use ($creatorId) {
                $q->posted()
                  ->where('reference_type', 'order')
                  ->whereHas('reference', fn($q2) => $q2->where('creator_id', $creatorId));
            })
            ->sum('credit');

        // Débits (payouts)
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

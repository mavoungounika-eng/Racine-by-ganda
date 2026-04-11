<?php

namespace Tests\Feature\Accounting;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Order;
use App\Models\User;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Events\PaymentRecorded;
use Illuminate\Support\Facades\Event;
use Tests\Traits\SeedsAccounting;

class PaymentAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $user;
    protected Journal $journal;
    protected FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();

        // CrÃ©er utilisateur
        $this->user = User::factory()->create();

        // Seed donnÃ©es comptables via trait explicite
        $this->seedAccounting();

        $this->journal = Journal::where('code', 'VTE')->first();
        $this->fiscalYear = FiscalYear::where('is_closed', false)->first();
    }
    #[Test]
    public function it_creates_accounting_entry_for_stripe_payment()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'pending',
        ]);

        // Simuler paiement confirmÃ©
        $order->update(['payment_status' => 'paid']);

        // VÃ©rifier Ã©criture crÃ©Ã©e
        $entry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->is_posted);
        $this->assertEquals(118.00, $entry->total_debit);
        $this->assertEquals(118.00, $entry->total_credit);

        // VÃ©rifier lignes
        $lines = $entry->lines;
        $this->assertCount(3, $lines);

        // Ligne 1: DÃ©bit Stripe (TTC)
        $stripeLine = $lines->where('account_code', '5112')->first();
        $this->assertNotNull($stripeLine);
        $this->assertEquals(118.00, $stripeLine->debit);

        // Ligne 2: CrÃ©dit Ventes (HT)
        $salesLine = $lines->where('account_code', '7011')->first();
        $this->assertNotNull($salesLine);
        $this->assertEquals(100.00, $salesLine->credit);
        $this->assertEquals(100.00, $salesLine->amount_ht);
        $this->assertEquals(18.00, $salesLine->vat_amount);

        // Ligne 3: CrÃ©dit TVA
        $vatLine = $lines->where('account_code', '4421')->first();
        $this->assertNotNull($vatLine);
        $this->assertEquals(18.00, $vatLine->credit);
    }
    #[Test]
    public function it_creates_accounting_entry_for_mobile_money_payment()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 59.00,
            'payment_method' => 'mobile_money',
            'payment_status' => 'pending',
        ]);

        $order->update(['payment_status' => 'paid']);

        $entry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNotNull($entry);

        // VÃ©rifier compte dÃ©bit Monetbil
        $monetbilLine = $entry->lines->where('account_code', '5113')->first();
        $this->assertNotNull($monetbilLine);
        $this->assertEquals(59.00, $monetbilLine->debit);
    }
    #[Test]
    public function it_creates_accounting_entry_for_cash_payment()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 236.00,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);

        $order->update(['payment_status' => 'paid']);

        $entry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNotNull($entry);

        // VÃ©rifier compte dÃ©bit Caisse
        $cashLine = $entry->lines->where('account_code', '5700')->first();
        $this->assertNotNull($cashLine);
        $this->assertEquals(236.00, $cashLine->debit);
    }
    #[Test]
    public function it_creates_marketplace_accounting_entry_with_commission()
    {
        $creator = User::factory()->create(['role' => 'createur']);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'creator_id' => $creator->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'pending',
        ]);

        $order->update(['payment_status' => 'paid']);

        // SaaS Pur: Les ventes crÃ©ateurs sont comptabilisÃ©es via CreatorSaleRecord, pas via le Ledger
        $entry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNull($entry, 'SaaS Pur: Aucune entrÃ©e comptable ne doit Ãªtre crÃ©Ã©e pour une vente crÃ©ateur.');
    }
    #[Test]
    public function it_does_not_create_entry_if_payment_not_confirmed()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'pending',
        ]);

        // Pas de changement vers 'paid'

        $entry = AccountingEntry::where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->first();

        $this->assertNull($entry);
    }
    #[Test]
    public function it_dispatches_payment_recorded_event()
    {
        Event::fake([PaymentRecorded::class]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'total_amount' => 118.00,
            'payment_method' => 'card',
            'payment_status' => 'pending',
        ]);

        $order->update(['payment_status' => 'paid']);

        Event::assertDispatched(PaymentRecorded::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }
}

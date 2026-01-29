<?php

namespace Tests\Feature\Accounting;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpSupplier;
use App\Models\User;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Events\PurchaseReceived;
use Illuminate\Support\Facades\Event;

class PurchaseAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected ErpSupplier $supplier;
    protected Journal $journal;
    protected FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        $this->journal = Journal::where('code', 'ACH')->first();
        $this->fiscalYear = FiscalYear::current()->first();

        // Créer fournisseur
        $this->supplier = ErpSupplier::create([
            'name' => 'Fournisseur Test Tissus',
            'email' => 'fournisseur@test.com',
            'phone' => '0600000000',
            'address' => 'Pointe-Noire',
            'type' => 'fabric',
        ]);
    }

    /** @test */
    public function it_creates_accounting_entry_when_purchase_is_received()
    {
        $purchase = ErpPurchase::create([
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'total' => 590.00, // 500 HT + 90 TVA (18%)
            'status' => 'pending',
        ]);

        // Simuler réception
        $purchase->update(['status' => 'received']);

        // Vérifier écriture créée
        $entry = AccountingEntry::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue($entry->is_posted);
        $this->assertEquals(590.00, $entry->total_debit);
        $this->assertEquals(590.00, $entry->total_credit);

        // Vérifier lignes
        $lines = $entry->lines;
        $this->assertCount(3, $lines);

        // Ligne 1: Débit Achats tissus (HT)
        $purchaseLine = $lines->where('account_code', '6011')->first();
        $this->assertNotNull($purchaseLine);
        $this->assertEquals(500.00, $purchaseLine->debit);
        $this->assertEquals(500.00, $purchaseLine->amount_ht);
        $this->assertEquals(90.00, $purchaseLine->vat_amount);

        // Ligne 2: Débit TVA déductible
        $vatLine = $lines->where('account_code', '4422')->first();
        $this->assertNotNull($vatLine);
        $this->assertEquals(90.00, $vatLine->debit);

        // Ligne 3: Crédit Fournisseur (TTC)
        $supplierLine = $lines->where('account_code', '4011')->first();
        $this->assertNotNull($supplierLine);
        $this->assertEquals(590.00, $supplierLine->credit);
    }

    /** @test */
    public function it_does_not_create_entry_if_purchase_not_received()
    {
        $purchase = ErpPurchase::create([
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'total' => 590.00,
            'status' => 'pending',
        ]);

        // Pas de changement vers 'received'

        $entry = AccountingEntry::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->first();

        $this->assertNull($entry);
    }

    /** @test */
    public function it_dispatches_purchase_received_event()
    {
        Event::fake([PurchaseReceived::class]);

        $purchase = ErpPurchase::create([
            'supplier_id' => $this->supplier->id,
            'purchase_date' => now(),
            'total' => 590.00,
            'status' => 'pending',
        ]);

        $purchase->update(['status' => 'received']);

        Event::assertDispatched(PurchaseReceived::class, function ($event) use ($purchase) {
            return $event->purchase->id === $purchase->id;
        });
    }

    /** @test */
    public function it_calculates_vat_correctly_for_different_amounts()
    {
        $testCases = [
            ['total' => 118.00, 'expected_ht' => 100.00, 'expected_vat' => 18.00],
            ['total' => 590.00, 'expected_ht' => 500.00, 'expected_vat' => 90.00],
            ['total' => 1180.00, 'expected_ht' => 1000.00, 'expected_vat' => 180.00],
        ];

        foreach ($testCases as $testCase) {
            $purchase = ErpPurchase::create([
                'supplier_id' => $this->supplier->id,
                'purchase_date' => now(),
                'total' => $testCase['total'],
                'status' => 'received',
            ]);

            $entry = AccountingEntry::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->first();

            $purchaseLine = $entry->lines->where('account_code', '6011')->first();
            $this->assertEquals($testCase['expected_ht'], $purchaseLine->amount_ht);
            $this->assertEquals($testCase['expected_vat'], $purchaseLine->vat_amount);
        }
    }
}

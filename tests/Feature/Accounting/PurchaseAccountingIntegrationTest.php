<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Accounting\Events\PurchaseReceived;
use Modules\Accounting\Models\AccountingEntry;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpSupplier;
use Tests\TestCase;
use Tests\Traits\SeedsAccounting;

class PurchaseAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $user;
    protected ErpSupplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // Force synchronous queued listeners for deterministic integration tests.
        config(['queue.default' => 'sync']);

        $this->seedAccounting();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->supplier = ErpSupplier::create([
            'name' => 'Supplier Test',
            'is_active' => true,
        ]);
    }

    public function test_it_creates_accounting_entry_when_purchase_is_received(): void
    {
        $purchase = ErpPurchase::create([
            'reference' => 'PO-TEST-001',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 590.00, // 500 HT + 90 TVA (18%)
            'status' => 'ordered',
        ]);

        $purchase->update(['status' => 'received']);

        $entry = AccountingEntry::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->with('lines')
            ->first();

        $this->assertNotNull($entry);
        $this->assertTrue((bool) $entry->is_posted);
        $this->assertEqualsWithDelta(590.00, (float) $entry->total_debit, 0.01);
        $this->assertEqualsWithDelta(590.00, (float) $entry->total_credit, 0.01);

        $lines = $entry->lines;
        $this->assertCount(3, $lines);

        $purchaseLine = $lines->where('account_code', '6011')->first();
        $this->assertNotNull($purchaseLine);
        $this->assertEqualsWithDelta(500.00, (float) $purchaseLine->debit, 0.01);
        $this->assertEqualsWithDelta(500.00, (float) $purchaseLine->amount_ht, 0.01);
        $this->assertEqualsWithDelta(90.00, (float) $purchaseLine->vat_amount, 0.01);

        $vatLine = $lines->where('account_code', '4422')->first();
        $this->assertNotNull($vatLine);
        $this->assertEqualsWithDelta(90.00, (float) $vatLine->debit, 0.01);

        $supplierLine = $lines->where('account_code', '4011')->first();
        $this->assertNotNull($supplierLine);
        $this->assertEqualsWithDelta(590.00, (float) $supplierLine->credit, 0.01);
    }

    public function test_it_does_not_create_entry_if_purchase_not_received(): void
    {
        $purchase = ErpPurchase::create([
            'reference' => 'PO-TEST-002',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 590.00,
            'status' => 'ordered',
        ]);

        $entry = AccountingEntry::where('reference_type', 'purchase')
            ->where('reference_id', $purchase->id)
            ->first();

        $this->assertNull($entry);
    }

    public function test_it_dispatches_purchase_received_event(): void
    {
        Event::fake([PurchaseReceived::class]);

        $purchase = ErpPurchase::create([
            'reference' => 'PO-TEST-003',
            'supplier_id' => $this->supplier->id,
            'user_id' => $this->user->id,
            'purchase_date' => now()->toDateString(),
            'total_amount' => 590.00,
            'status' => 'ordered',
        ]);

        $purchase->update(['status' => 'received']);

        Event::assertDispatched(PurchaseReceived::class, function (PurchaseReceived $event) use ($purchase) {
            return $event->purchase->id === $purchase->id;
        });
    }

    public function test_it_calculates_vat_correctly_for_different_amounts(): void
    {
        $testCases = [
            ['total' => 118.00, 'expected_ht' => 100.00, 'expected_vat' => 18.00],
            ['total' => 590.00, 'expected_ht' => 500.00, 'expected_vat' => 90.00],
            ['total' => 1180.00, 'expected_ht' => 1000.00, 'expected_vat' => 180.00],
        ];

        foreach ($testCases as $index => $testCase) {
            $purchase = ErpPurchase::create([
                'reference' => 'PO-VAT-' . $index,
                'supplier_id' => $this->supplier->id,
                'user_id' => $this->user->id,
                'purchase_date' => now()->toDateString(),
                'total_amount' => $testCase['total'],
                'status' => 'ordered',
            ]);

            $purchase->update(['status' => 'received']);

            $entry = AccountingEntry::where('reference_type', 'purchase')
                ->where('reference_id', $purchase->id)
                ->with('lines')
                ->first();

            $this->assertNotNull($entry);

            $purchaseLine = $entry->lines->where('account_code', '6011')->first();
            $this->assertNotNull($purchaseLine);
            $this->assertEqualsWithDelta($testCase['expected_ht'], (float) $purchaseLine->amount_ht, 0.01);
            $this->assertEqualsWithDelta($testCase['expected_vat'], (float) $purchaseLine->vat_amount, 0.01);
        }
    }
}

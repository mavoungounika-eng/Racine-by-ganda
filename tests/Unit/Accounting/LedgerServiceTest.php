<?php

namespace Tests\Unit\Accounting;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Exceptions\LedgerException;
use App\Models\User;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $ledgerService;
    protected User $user;
    protected Journal $journal;
    protected FiscalYear $fiscalYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ledgerService = new LedgerService();
        
        // Créer utilisateur test
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Créer journal test
        $this->journal = Journal::create([
            'code' => 'TST',
            'name' => 'Journal Test',
            'type' => 'general',
            'is_active' => true,
        ]);

        // Créer exercice test
        $this->fiscalYear = FiscalYear::create([
            'name' => 'Exercice Test 2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_closed' => false,
        ]);

        // Créer comptes test
        ChartOfAccount::create([
            'code' => '5210',
            'label' => 'Banque test',
            'account_type' => 'asset',
            'normal_balance' => 'debit',
            'is_active' => true,
        ]);

        ChartOfAccount::create([
            'code' => '7011',
            'label' => 'Ventes test',
            'account_type' => 'revenue',
            'normal_balance' => 'credit',
            'is_active' => true,
            'requires_vat' => true,
        ]);

        ChartOfAccount::create([
            'code' => '4421',
            'label' => 'TVA collectée test',
            'account_type' => 'liability',
            'normal_balance' => 'credit',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_create_an_accounting_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->assertInstanceOf(AccountingEntry::class, $entry);
        $this->assertEquals('TST-2025-001', $entry->entry_number);
        $this->assertEquals('Test entry', $entry->description);
        $this->assertFalse($entry->is_posted);
    }

    /** @test */
    public function it_generates_sequential_entry_numbers()
    {
        $entry1 = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Entry 1',
        ]);

        $entry2 = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-16',
            'description' => 'Entry 2',
        ]);

        $this->assertEquals('TST-2025-001', $entry1->entry_number);
        $this->assertEquals('TST-2025-002', $entry2->entry_number);
    }

    /** @test */
    public function it_throws_exception_when_fiscal_year_is_closed()
    {
        $this->fiscalYear->update(['is_closed' => true]);

        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('est clôturé');

        $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);
    }

    /** @test */
    public function it_can_add_line_to_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $line = $this->ledgerService->addLine(
            $entry,
            '5210',
            100.00,
            0,
            'Test debit line'
        );

        $this->assertEquals('5210', $line->account_code);
        $this->assertEquals(100.00, $line->debit);
        $this->assertEquals(0, $line->credit);
        $this->assertEquals(1, $line->line_number);
    }

    /** @test */
    public function it_throws_exception_when_adding_line_with_both_debit_and_credit()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('débit OU crédit');

        $this->ledgerService->addLine($entry, '5210', 100.00, 50.00);
    }

    /** @test */
    public function it_throws_exception_when_adding_line_with_neither_debit_nor_credit()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->expectException(LedgerException::class);

        $this->ledgerService->addLine($entry, '5210', 0, 0);
    }

    /** @test */
    public function it_recalculates_totals_when_adding_lines()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $entry->refresh();
        $this->assertEquals(100.00, $entry->total_debit);
        $this->assertEquals(0, $entry->total_credit);

        $this->ledgerService->addLine($entry, '7011', 0, 100.00);
        $entry->refresh();
        $this->assertEquals(100.00, $entry->total_debit);
        $this->assertEquals(100.00, $entry->total_credit);
    }

    /** @test */
    public function it_validates_balanced_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $this->ledgerService->addLine($entry, '7011', 0, 100.00);

        $this->assertTrue($this->ledgerService->validateBalance($entry));
    }

    /** @test */
    public function it_detects_unbalanced_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $this->ledgerService->addLine($entry, '7011', 0, 50.00);

        $this->assertFalse($this->ledgerService->validateBalance($entry));
    }

    /** @test */
    public function it_can_post_balanced_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $this->ledgerService->addLine($entry, '7011', 0, 100.00);

        $this->ledgerService->postEntry($entry);

        $entry->refresh();
        $this->assertTrue($entry->is_posted);
        $this->assertNotNull($entry->posted_at);
        $this->assertEquals($this->user->id, $entry->posted_by);
    }

    /** @test */
    public function it_throws_exception_when_posting_unbalanced_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $this->ledgerService->addLine($entry, '7011', 0, 50.00);

        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('non équilibrée');

        $this->ledgerService->postEntry($entry);
    }

    /** @test */
    public function it_throws_exception_when_posting_already_posted_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $this->ledgerService->addLine($entry, '7011', 0, 100.00);
        $this->ledgerService->postEntry($entry);

        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('déjà postée');

        $this->ledgerService->postEntry($entry);
    }

    /** @test */
    public function it_prevents_adding_lines_to_posted_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0);
        $this->ledgerService->addLine($entry, '7011', 0, 100.00);
        $this->ledgerService->postEntry($entry);

        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('irréversible');

        $this->ledgerService->addLine($entry, '5210', 50.00, 0);
    }

    /** @test */
    public function it_handles_vat_in_entry_lines()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Test entry with VAT',
        ]);

        $line = $this->ledgerService->addLine(
            $entry,
            '7011',
            0,
            100.00,
            'Sales with VAT',
            [
                'amount_ht' => 100.00,
                'vat_amount' => 18.00,
                'vat_rate' => 18.00,
            ]
        );

        $this->assertEquals(100.00, $line->amount_ht);
        $this->assertEquals(18.00, $line->vat_amount);
        $this->assertEquals(18.00, $line->vat_rate);
    }

    /** @test */
    public function it_creates_sale_entry_with_vat()
    {
        $order = (object) ['id' => 123];

        $entry = $this->ledgerService->createSaleEntry(
            $order,
            'TST',
            '5210',
            '7011',
            118.00,
            18.0
        );

        $this->assertTrue($entry->is_posted);
        $this->assertEquals(3, $entry->lines->count());
        
        // Vérifier ligne débit banque (TTC)
        $debitLine = $entry->lines->where('account_code', '5210')->first();
        $this->assertEquals(118.00, $debitLine->debit);

        // Vérifier ligne crédit ventes (HT)
        $salesLine = $entry->lines->where('account_code', '7011')->first();
        $this->assertEquals(100.00, $salesLine->credit);
        $this->assertEquals(100.00, $salesLine->amount_ht);
        $this->assertEquals(18.00, $salesLine->vat_amount);

        // Vérifier ligne crédit TVA
        $vatLine = $entry->lines->where('account_code', '4421')->first();
        $this->assertEquals(18.00, $vatLine->credit);

        // Vérifier équilibre
        $this->assertTrue($entry->isBalanced());
    }

    /** @test */
    public function it_can_reverse_posted_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Original entry',
        ]);

        $this->ledgerService->addLine($entry, '5210', 100.00, 0, 'Debit bank');
        $this->ledgerService->addLine($entry, '7011', 0, 100.00, 'Credit sales');
        $this->ledgerService->postEntry($entry);

        $reversalEntry = $this->ledgerService->reverseEntry($entry, 'Correction error');

        $this->assertTrue($reversalEntry->is_posted);
        $this->assertStringContainsString('CONTRE-PASSATION', $reversalEntry->description);
        $this->assertEquals(2, $reversalEntry->lines->count());

        // Vérifier inversion des lignes
        $reversalDebitLine = $reversalEntry->lines->where('account_code', '7011')->first();
        $this->assertEquals(100.00, $reversalDebitLine->debit);

        $reversalCreditLine = $reversalEntry->lines->where('account_code', '5210')->first();
        $this->assertEquals(100.00, $reversalCreditLine->credit);
    }

    /** @test */
    public function it_throws_exception_when_reversing_unposted_entry()
    {
        $entry = $this->ledgerService->createEntry([
            'journal_id' => $this->journal->id,
            'fiscal_year_id' => $this->fiscalYear->id,
            'entry_date' => '2025-06-15',
            'description' => 'Draft entry',
        ]);

        $this->expectException(LedgerException::class);
        $this->expectExceptionMessage('Seules les écritures postées');

        $this->ledgerService->reverseEntry($entry, 'Test');
    }
}

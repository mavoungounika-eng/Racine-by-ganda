<?php

namespace Tests\Architecture;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\ForbiddenCreationException;

/**
 * Tests d'architecture pour le verrouillage du LedgerService
 * 
 * Ces tests garantissent que:
 * 1. AccountingEntry::create() direct → ForbiddenCreationException
 * 2. Création via LedgerService → OK
 * 3. LedgerService est final (non-extensible)
 */
class LedgerServiceArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::factory()->create();
        $this->actingAs($user);
        
        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);
    }

    /**
     * @test
     * RÈGLE: AccountingEntry::create() direct est INTERDIT
     */
    public function direct_accounting_entry_creation_throws_exception()
    {
        $this->expectException(ForbiddenCreationException::class);
        $this->expectExceptionMessage("AccountingEntry::create() interdit");

        AccountingEntry::create([
            'entry_number' => 'TEST-001',
            'journal_id' => 1,
            'fiscal_year_id' => 1,
            'entry_date' => now()->toDateString(),
            'description' => 'Test direct creation',
            'created_by' => 1,
        ]);
    }

    /**
     * @test
     * RÈGLE: Création via LedgerService est AUTORISÉE
     */
    public function creation_via_ledger_service_is_allowed()
    {
        $ledgerService = app(LedgerService::class);
        
        $journal = \Modules\Accounting\Models\Journal::where('code', 'VTE')->first();
        $fiscalYear = \Modules\Accounting\Models\FiscalYear::where('is_closed', false)->first();

        $entry = $ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Test via LedgerService',
            'reference_type' => 'test',
            'reference_id' => 999,
        ]);

        $this->assertInstanceOf(AccountingEntry::class, $entry);
        $this->assertNotNull($entry->id);
        $this->assertEquals('Test via LedgerService', $entry->description);
    }

    /**
     * @test
     * RÈGLE: LedgerService est une classe FINAL (non-extensible)
     */
    public function ledger_service_is_final_class()
    {
        $reflection = new \ReflectionClass(LedgerService::class);
        
        $this->assertTrue(
            $reflection->isFinal(),
            'LedgerService DOIT être final pour empêcher héritage non-contrôlé'
        );
    }

    /**
     * @test
     * RÈGLE: Le flag container est nettoyé après création
     */
    public function container_flag_is_cleaned_after_creation()
    {
        $ledgerService = app(LedgerService::class);
        
        $journal = \Modules\Accounting\Models\Journal::where('code', 'VTE')->first();
        $fiscalYear = \Modules\Accounting\Models\FiscalYear::where('is_closed', false)->first();

        // Créer une entrée
        $ledgerService->createEntry([
            'journal_id' => $journal->id,
            'fiscal_year_id' => $fiscalYear->id,
            'entry_date' => now()->toDateString(),
            'description' => 'Test cleanup',
            'reference_type' => 'test',
            'reference_id' => 888,
        ]);

        // Vérifier que le flag n'existe plus
        $this->assertFalse(
            app()->bound('ledger.creating.allowed'),
            'Le flag ledger.creating.allowed ne doit pas persister après création'
        );
    }

    /**
     * @test
     * RÈGLE: Même après exception, le flag est nettoyé
     */
    public function container_flag_is_cleaned_even_on_exception()
    {
        $ledgerService = app(LedgerService::class);

        try {
            // Provoquer une erreur (fiscal year invalide)
            $ledgerService->createEntry([
                'journal_id' => 1,
                'fiscal_year_id' => 99999, // N'existe pas
                'entry_date' => now()->toDateString(),
                'description' => 'Test exception cleanup',
            ]);
        } catch (\Exception $e) {
            // Exception attendue
        }

        // Vérifier que le flag n'existe plus
        $this->assertFalse(
            app()->bound('ledger.creating.allowed'),
            'Le flag doit être nettoyé même en cas d\'exception'
        );
    }
}

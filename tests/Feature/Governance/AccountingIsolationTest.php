<?php

namespace Tests\Feature\Governance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\SeedsAccounting;

/**
 * Tests anti-régression pour l'isolation comptable.
 * 
 * Ces tests garantissent que:
 * 1. Les tests comptables ne dépendent PAS de seeders globaux
 * 2. Le trait SeedsAccounting crée bien les données nécessaires
 * 3. Sans seeding, les requêtes comptables échouent proprement
 */
class AccountingIsolationTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    // =========================================================================
    // TESTS POSITIFS: Avec seeding explicite
    // =========================================================================

    /** @test */
    public function accounting_data_exists_after_explicit_seeding(): void
    {
        // SANS seeding → rien
        $this->assertDatabaseMissing('accounting_fiscal_years', ['is_closed' => false]);
        
        // AVEC seeding explicite
        $this->seedAccounting();
        
        // Toutes les données minimales existent
        $this->assertAccountingSeeded();
        $this->assertDatabaseHas('accounting_journals', ['code' => 'VTE']);
        $this->assertDatabaseHas('accounting_journals', ['code' => 'BNQ']);
        $this->assertDatabaseHas('accounting_chart_of_accounts', ['code' => '5112']);
        $this->assertDatabaseHas('accounting_chart_of_accounts', ['code' => '7011']);
    }

    /** @test */
    public function seed_accounting_is_idempotent(): void
    {
        $this->seedAccounting();
        $this->seedAccounting(); // Appel multiple
        $this->seedAccounting();
        
        // Toujours 1 seul fiscal year
        $count = \Modules\Accounting\Models\FiscalYear::count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function seed_accounting_and_return_provides_objects(): void
    {
        $data = $this->seedAccountingAndReturn();
        
        $this->assertArrayHasKey('fiscalYear', $data);
        $this->assertArrayHasKey('journalVTE', $data);
        $this->assertArrayHasKey('journalBNQ', $data);
        
        $this->assertInstanceOf(\Modules\Accounting\Models\FiscalYear::class, $data['fiscalYear']);
        $this->assertInstanceOf(\Modules\Accounting\Models\Journal::class, $data['journalVTE']);
    }

    // =========================================================================
    // TESTS NÉGATIFS: Sans seeding
    // =========================================================================

    /** @test */
    public function fiscal_year_query_returns_null_without_seeding(): void
    {
        // DB vide
        $fiscalYear = \Modules\Accounting\Models\FiscalYear::where('is_closed', false)->first();
        
        $this->assertNull($fiscalYear);
    }

    /** @test */
    public function journal_query_returns_null_without_seeding(): void
    {
        $journal = \Modules\Accounting\Models\Journal::where('code', 'VTE')->first();
        
        $this->assertNull($journal);
    }

    // =========================================================================
    // TESTS ANTI-RÉGRESSION
    // =========================================================================

    /** @test */
    public function no_global_seeder_dependency_in_test_setup(): void
    {
        // Ce test prouve qu'un test peut s'exécuter sur DB vide
        // et utiliser seedAccounting() à la demande
        
        $this->assertDatabaseCount('accounting_fiscal_years', 0);
        $this->assertDatabaseCount('accounting_journals', 0);
        
        $this->seedAccounting();
        
        $this->assertDatabaseCount('accounting_fiscal_years', 1);
        $this->assertDatabaseCount('accounting_journals', 4);
    }

    /** @test */
    public function accounting_test_seeder_creates_minimum_required_accounts(): void
    {
        $this->seedAccounting();
        
        // Comptes obligatoires pour écritures
        $requiredCodes = ['5112', '5113', '5211', '5212', '5700', '4421', '7011', '7071', '6011', '6271', '4011'];
        
        foreach ($requiredCodes as $code) {
            $this->assertDatabaseHas('accounting_chart_of_accounts', ['code' => $code], null, 
                "Compte OHADA {$code} manquant");
        }
    }
}

<?php

namespace Tests\Traits;

use Database\Seeders\Tests\AccountingTestSeeder;

/**
 * Trait pour isolation des tests comptables.
 * 
 * USAGE:
 * use Tests\Traits\SeedsAccounting;
 * 
 * protected function setUp(): void
 * {
 *     parent::setUp();
 *     $this->seedAccounting();
 * }
 * 
 * GARANTIES:
 * - Données comptables minimales créées explicitement
 * - Aucune dépendance à DatabaseSeeder ou migrate --seed
 * - Idempotent (peut être appelé plusieurs fois)
 */
trait SeedsAccounting
{
    /**
     * Seed données comptables minimum pour tests.
     * Idempotent - utilise firstOrCreate.
     */
    protected function seedAccounting(): void
    {
        $this->seed(AccountingTestSeeder::class);
    }

    /**
     * Seed + retourne les objets créés pour assertions.
     */
    protected function seedAccountingAndReturn(): array
    {
        $this->seedAccounting();

        return [
            'fiscalYear' => \Modules\Accounting\Models\FiscalYear::where('is_closed', false)->first(),
            'journalVTE' => \Modules\Accounting\Models\Journal::where('code', 'VTE')->first(),
            'journalBNQ' => \Modules\Accounting\Models\Journal::where('code', 'BNQ')->first(),
        ];
    }

    /**
     * Assert que les données comptables existent.
     * Utile pour tests anti-régression.
     */
    protected function assertAccountingSeeded(): void
    {
        $this->assertDatabaseHas('accounting_fiscal_years', ['is_closed' => false]);
        $this->assertDatabaseHas('accounting_journals', ['code' => 'VTE']);
        $this->assertDatabaseHas('accounting_chart_of_accounts', ['code' => '7011']);
    }
}

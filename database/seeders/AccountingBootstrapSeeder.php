<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\ChartOfAccount;

/**
 * AccountingBootstrapSeeder
 * 
 * RESPONSABILITÉ:
 * Créer l'environnement comptable minimal requis pour le POS.
 * 
 * USAGE PRODUCTION:
 * php artisan db:seed --class=AccountingBootstrapSeeder
 * 
 * ⚠️ Ce seeder DOIT être appelé explicitement en production.
 * ⚠️ NE PAS inclure dans DatabaseSeeder automatique.
 */
class AccountingBootstrapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🔧 Bootstrapping accounting environment for POS...');

        // 1. Exercice fiscal courant
        $fiscalYear = FiscalYear::firstOrCreate(
            ['name' => '2026'],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_closed' => false,
            ]
        );
        $this->command->info("✅ Fiscal year: {$fiscalYear->name}");

        // 2. Journal Ventes
        $journal = Journal::firstOrCreate(
            ['code' => 'VTE'],
            [
                'name' => 'Journal des Ventes',
                'type' => 'sales', // Enum: purchases, sales, bank, cash, general
            ]
        );
        $this->command->info("✅ Journal: {$journal->code} - {$journal->name}");

        // 3. Comptes OHADA requis
        $accounts = [
            [
                'code' => '5700',
                'label' => 'Caisse',
                'account_type' => 'asset',
                'normal_balance' => 'debit',
                'is_active' => true,
            ],
            [
                'code' => '7011',
                'label' => 'Ventes de marchandises',
                'account_type' => 'revenue',
                'normal_balance' => 'credit',
                'is_active' => true,
            ],
            [
                'code' => '4431',
                'label' => 'TVA collectée',
                'account_type' => 'liability',
                'normal_balance' => 'credit',
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $accountData) {
            $account = ChartOfAccount::firstOrCreate(
                ['code' => $accountData['code']],
                $accountData
            );
            $this->command->info("✅ Account: {$account->code} - {$account->label}");
        }

        $this->command->info('');
        $this->command->info('🎯 Accounting bootstrap complete!');
        $this->command->info('   POS settlement is now ready.');
    }
}

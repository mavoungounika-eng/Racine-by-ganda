<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ChartOfAccountsSeeder::class,
            JournalsSeeder::class,
            FiscalYearsSeeder::class,
        ]);

        $this->command->info('✅ Module Accounting seedé avec succès!');
    }
}

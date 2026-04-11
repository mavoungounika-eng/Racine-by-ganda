<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\FiscalYear;
use Carbon\Carbon;

class FiscalYearsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = Carbon::now()->year;

        FiscalYear::create([
            'name' => "Exercice {$currentYear}",
            'start_date' => Carbon::create($currentYear, 1, 1),
            'end_date' => Carbon::create($currentYear, 12, 31),
            'is_closed' => false,
        ]);

        $this->command->info("✅ Exercice comptable {$currentYear} créé avec succès!");
    }
}

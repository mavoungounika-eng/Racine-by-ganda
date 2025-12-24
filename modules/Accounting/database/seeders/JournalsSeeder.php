<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\Journal;

class JournalsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $journals = [
            [
                'code' => 'VTE',
                'name' => 'Journal des ventes',
                'type' => 'sales',
                'description' => 'Enregistrement des ventes (boutique + marketplace)',
            ],
            [
                'code' => 'ACH',
                'name' => 'Journal des achats',
                'type' => 'purchases',
                'description' => 'Enregistrement des achats de matières premières et fournitures',
            ],
            [
                'code' => 'BNQ',
                'name' => 'Journal de banque',
                'type' => 'bank',
                'description' => 'Opérations bancaires (virements, prélèvements, rapprochements)',
            ],
            [
                'code' => 'CAI',
                'name' => 'Journal de caisse',
                'type' => 'cash',
                'description' => 'Opérations de caisse (encaissements cash, dépenses)',
            ],
            [
                'code' => 'OD',
                'name' => 'Journal des opérations diverses',
                'type' => 'general',
                'description' => 'Écritures diverses (régularisations, corrections, clôtures)',
            ],
        ];

        foreach ($journals as $journal) {
            Journal::create($journal);
        }

        $this->command->info('✅ Journaux comptables seedés avec succès!');
    }
}

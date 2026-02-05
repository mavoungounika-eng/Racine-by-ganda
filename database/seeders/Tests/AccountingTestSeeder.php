<?php

namespace Database\Seeders\Tests;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Models\ChartOfAccount;

/**
 * Seeder déterministe STRICTEMENT réservé aux tests.
 * 
 * INVARIANTS GARANTIS:
 * - 1 FiscalYear ouvert (exercice courant)
 * - 4 Journals (VTE, ACH, BNQ, CAI)
 * - Comptes OHADA minimum pour écritures comptables
 * 
 * AUCUN aléatoire. AUCUNE dépendance externe.
 */
class AccountingTestSeeder extends Seeder
{
    public function run(): void
    {
        // 1. FiscalYear ouvert
        FiscalYear::firstOrCreate(
            ['name' => 'Test Fiscal Year'],
            [
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_closed' => false,
            ]
        );

        // 2. Journals
        $journals = [
            ['code' => 'VTE', 'name' => 'Journal des Ventes', 'type' => 'sales'],
            ['code' => 'ACH', 'name' => 'Journal des Achats', 'type' => 'purchases'],
            ['code' => 'BNQ', 'name' => 'Journal de Banque', 'type' => 'bank'],
            ['code' => 'CAI', 'name' => 'Journal de Caisse', 'type' => 'cash'],
        ];

        foreach ($journals as $journal) {
            Journal::firstOrCreate(['code' => $journal['code']], $journal);
        }

        // 3. Comptes OHADA minimum
        $accounts = [
            // Actifs (débit normal)
            ['code' => '5112', 'label' => 'Banque Carte Bancaire', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5113', 'label' => 'Encaissements Mobile Money', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5211', 'label' => 'Banque Stripe', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5212', 'label' => 'Banque Monetbil', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5700', 'label' => 'Caisse', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            
            // Passifs (crédit normal)
            ['code' => '4421', 'label' => 'TVA collectée', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '4011', 'label' => 'Fournisseurs', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            
            // Revenus (crédit normal)
            ['code' => '7011', 'label' => 'Ventes de marchandises', 'account_type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '7071', 'label' => 'Commissions marketplace', 'account_type' => 'revenue', 'normal_balance' => 'credit'],
            
            // Charges (débit normal)
            ['code' => '6011', 'label' => 'Achats de marchandises', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '6271', 'label' => 'Frais bancaires', 'account_type' => 'expense', 'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(['code' => $account['code']], $account);
        }
    }
}

<?php

namespace Database\Seeders\Tests;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\ChartOfAccount;
use Modules\Accounting\Models\FiscalYear;
use Modules\Accounting\Models\Journal;

/**
 * Deterministic accounting seed reserved for tests.
 */
class AccountingTestSeeder extends Seeder
{
    public function run(): void
    {
        FiscalYear::firstOrCreate(
            ['name' => 'Test Fiscal Year'],
            [
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_closed' => false,
            ]
        );

        $journals = [
            ['code' => 'VTE', 'name' => 'Journal des Ventes', 'type' => 'sales'],
            ['code' => 'ACH', 'name' => 'Journal des Achats', 'type' => 'purchases'],
            ['code' => 'BNQ', 'name' => 'Journal de Banque', 'type' => 'bank'],
            ['code' => 'CAI', 'name' => 'Journal de Caisse', 'type' => 'cash'],
            ['code' => 'OD', 'name' => 'Journal des Operations Diverses', 'type' => 'general'],
        ];

        foreach ($journals as $journal) {
            Journal::firstOrCreate(['code' => $journal['code']], $journal);
        }

        $accounts = [
            // Assets
            ['code' => '5112', 'label' => 'Banque Carte Bancaire', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5113', 'label' => 'Encaissements Mobile Money', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5211', 'label' => 'Banque Stripe', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5212', 'label' => 'Banque Monetbil', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '5700', 'label' => 'Caisse', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '4422', 'label' => 'TVA deductible', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '311', 'label' => 'Matieres premieres', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '331', 'label' => 'Encours de production', 'account_type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '351', 'label' => 'Produits finis', 'account_type' => 'asset', 'normal_balance' => 'debit'],

            // Liabilities
            ['code' => '4421', 'label' => 'TVA collectee', 'account_type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '4011', 'label' => 'Fournisseurs', 'account_type' => 'liability', 'normal_balance' => 'credit'],

            // Revenue
            ['code' => '7011', 'label' => 'Ventes de marchandises', 'account_type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '7071', 'label' => 'Commissions marketplace', 'account_type' => 'revenue', 'normal_balance' => 'credit'],

            // Expenses
            ['code' => '6011', 'label' => 'Achats de marchandises', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '6271', 'label' => 'Frais bancaires', 'account_type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '658', 'label' => 'Charges diverses', 'account_type' => 'expense', 'normal_balance' => 'debit'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::firstOrCreate(['code' => $account['code']], $account);
        }
    }
}

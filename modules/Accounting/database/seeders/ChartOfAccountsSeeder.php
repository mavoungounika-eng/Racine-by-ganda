<?php

namespace Modules\Accounting\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Accounting\Models\ChartOfAccount;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // CLASSE 4 - COMPTES DE TIERS
            ['code' => '401', 'label' => 'Fournisseurs', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => null],
            ['code' => '4011', 'label' => 'Fournisseurs tissus', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => '401'],
            ['code' => '4012', 'label' => 'Fournisseurs accessoires', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => '401'],
            ['code' => '4013', 'label' => 'Fournisseurs services', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => '401'],
            
            ['code' => '411', 'label' => 'Clients', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => null],
            ['code' => '4111', 'label' => 'Clients particuliers', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '411'],
            
            ['code' => '421', 'label' => 'Personnel', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => null],
            ['code' => '4211', 'label' => 'Personnel - Salaires à payer', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => '421'],
            
            ['code' => '442', 'label' => 'État - TVA', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => null],
            ['code' => '4421', 'label' => 'État - TVA collectée', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => '442'],
            ['code' => '4422', 'label' => 'État - TVA déductible', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '442'],
            
            ['code' => '467', 'label' => 'Créanciers divers', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => null],
            ['code' => '4671', 'label' => 'Créateurs marketplace', 'type' => 'liability', 'balance' => 'credit', 'vat' => false, 'parent' => '467'],
            
            // CLASSE 5 - COMPTES DE TRÉSORERIE
            ['code' => '511', 'label' => 'Valeurs à encaisser', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => null],
            ['code' => '5112', 'label' => 'Encaissements Stripe (attente)', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '511'],
            ['code' => '5113', 'label' => 'Encaissements Monetbil (attente)', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '511'],
            
            ['code' => '521', 'label' => 'Banques', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => null],
            ['code' => '5210', 'label' => 'Banque principale', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '521'],
            ['code' => '5211', 'label' => 'Banque Stripe', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '521'],
            ['code' => '5212', 'label' => 'Banque Monetbil', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '521'],
            
            ['code' => '57', 'label' => 'Caisse', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => null],
            ['code' => '5700', 'label' => 'Caisse boutique', 'type' => 'asset', 'balance' => 'debit', 'vat' => false, 'parent' => '57'],
            
            // CLASSE 6 - COMPTES DE CHARGES
            ['code' => '601', 'label' => 'Achats de marchandises', 'type' => 'expense', 'balance' => 'debit', 'vat' => true, 'parent' => null],
            ['code' => '6011', 'label' => 'Achats de tissus', 'type' => 'expense', 'balance' => 'debit', 'vat' => true, 'parent' => '601'],
            ['code' => '6012', 'label' => 'Achats d\'accessoires', 'type' => 'expense', 'balance' => 'debit', 'vat' => true, 'parent' => '601'],
            
            ['code' => '611', 'label' => 'Transports', 'type' => 'expense', 'balance' => 'debit', 'vat' => true, 'parent' => null],
            ['code' => '6111', 'label' => 'Frais de transport', 'type' => 'expense', 'balance' => 'debit', 'vat' => true, 'parent' => '611'],
            
            ['code' => '624', 'label' => 'Frais bancaires', 'type' => 'expense', 'balance' => 'debit', 'vat' => false, 'parent' => null],
            ['code' => '6241', 'label' => 'Frais Stripe', 'type' => 'expense', 'balance' => 'debit', 'vat' => false, 'parent' => '624'],
            ['code' => '6242', 'label' => 'Frais Monetbil', 'type' => 'expense', 'balance' => 'debit', 'vat' => false, 'parent' => '624'],
            
            ['code' => '661', 'label' => 'Salaires', 'type' => 'expense', 'balance' => 'debit', 'vat' => false, 'parent' => null],
            ['code' => '6611', 'label' => 'Salaires atelier', 'type' => 'expense', 'balance' => 'debit', 'vat' => false, 'parent' => '661'],
            ['code' => '6612', 'label' => 'Salaires boutique', 'type' => 'expense', 'balance' => 'debit', 'vat' => false, 'parent' => '661'],
            
            // CLASSE 7 - COMPTES DE PRODUITS
            ['code' => '701', 'label' => 'Ventes de produits', 'type' => 'revenue', 'balance' => 'credit', 'vat' => true, 'parent' => null],
            ['code' => '7011', 'label' => 'Ventes boutique RACINE', 'type' => 'revenue', 'balance' => 'credit', 'vat' => true, 'parent' => '701'],
            ['code' => '7012', 'label' => 'Ventes marketplace créateurs', 'type' => 'revenue', 'balance' => 'credit', 'vat' => true, 'parent' => '701'],
            ['code' => '7013', 'label' => 'Commissions marketplace', 'type' => 'revenue', 'balance' => 'credit', 'vat' => false, 'parent' => '701'],
        ];

        foreach ($accounts as $account) {
            ChartOfAccount::create([
                'code' => $account['code'],
                'label' => $account['label'],
                'account_type' => $account['type'],
                'normal_balance' => $account['balance'],
                'parent_code' => $account['parent'],
                'requires_vat' => $account['vat'],
                'is_active' => true,
                'is_system' => true,
            ]);
        }

        $this->command->info('✅ Plan comptable OHADA seedé avec succès!');
    }
}

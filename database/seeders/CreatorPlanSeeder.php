<?php

namespace Database\Seeders;

use App\Models\CreatorPlan;
use Illuminate\Database\Seeder;

class CreatorPlanSeeder extends Seeder
{
    public function run(): void
    {
        // Désactiver le plan free (créateurs existants en lecture seule)
        CreatorPlan::where('code', 'free')->update(['is_active' => false]);
        // Désactiver les anciens plans official/premium si présents
        CreatorPlan::whereIn('code', ['official', 'premium'])->update(['is_active' => false]);

        $plans = [
            [
                'code'            => 'atelier',
                'name'            => 'Atelier',
                'price'           => 15000.00,
                'quarterly_price' => 40500.00,
                'annual_price'    => 150000.00,
                'billing_cycle'   => 'monthly',
                'is_active'       => true,
                'products_limit'  => 80,
                'has_pos'         => false,
                'trial_days'      => 30,
                'description'     => 'Plan essentiel pour démarrer votre activité de créateur',
                'features' => [
                    'Jusqu\'à 80 produits',
                    'Dashboard basique',
                    'Gestion des commandes',
                    '30 jours d\'essai gratuit',
                ],
            ],
            [
                'code'            => 'maison',
                'name'            => 'Maison',
                'price'           => 35000.00,
                'quarterly_price' => 94500.00,
                'annual_price'    => 350000.00,
                'billing_cycle'   => 'monthly',
                'is_active'       => true,
                'products_limit'  => 250,
                'has_pos'         => false,
                'trial_days'      => 30,
                'description'     => 'Plan avancé avec analytics et exports',
                'features' => [
                    'Jusqu\'à 250 produits',
                    'Dashboard avancé',
                    'Analytics détaillées',
                    'Export des données',
                    'Add-on POS +8 000 XAF/mois',
                    '30 jours d\'essai gratuit',
                ],
            ],
            [
                'code'            => 'signature',
                'name'            => 'Signature',
                'price'           => 75000.00,
                'quarterly_price' => 202500.00,
                'annual_price'    => 750000.00,
                'billing_cycle'   => 'monthly',
                'is_active'       => true,
                'products_limit'  => -1,
                'has_pos'         => true,
                'trial_days'      => 30,
                'description'     => 'Plan complet avec POS Electron inclus',
                'features' => [
                    'Produits illimités',
                    'Dashboard premium',
                    'Analytics avancées',
                    'POS Electron inclus',
                    'Support dédié',
                    '30 jours d\'essai gratuit',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            CreatorPlan::updateOrCreate(
                ['code' => $planData['code']],
                $planData
            );
        }
    }
}

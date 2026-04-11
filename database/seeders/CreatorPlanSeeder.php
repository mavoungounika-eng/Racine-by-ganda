<?php

namespace Database\Seeders;

use App\Models\CreatorPlan;
use Illuminate\Database\Seeder;

class CreatorPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crée les 3 plans d'abonnement: FREE, OFFICIEL, PREMIUM
     */
    public function run(): void
    {
        $plans = [
            [
                'code' => 'free',
                'name' => 'Gratuit',
                'price' => 0,
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'description' => 'Plan gratuit pour démarrer votre activité de créateur',
                'features' => [
                    'Jusqu\'à 5 produits',
                    'Dashboard basique',
                    'Gestion des commandes',
                ],
            ],
            [
                'code' => 'official',
                'name' => 'Officiel',
                'price' => 5000.00, // 5 000 XAF / mois
                'annual_price' => 50000.00, // V2.1 : 50 000 XAF / an (10 mois = 17% de réduction)
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'description' => 'Plan officiel avec fonctionnalités avancées',
                'features' => [
                    'Produits illimités',
                    'Dashboard avancé',
                    'Statistiques détaillées',
                    'Gestion des collections',
                    'Support prioritaire',
                ],
            ],
            [
                'code' => 'premium',
                'name' => 'Premium',
                'price' => 15000.00, // 15 000 XAF / mois
                'annual_price' => 150000.00, // V2.1 : 150 000 XAF / an (10 mois = 17% de réduction)
                'billing_cycle' => 'monthly',
                'is_active' => true,
                'description' => 'Plan premium avec toutes les fonctionnalités',
                'features' => [
                    'Tout du plan Officiel',
                    'Dashboard premium',
                    'Analytics avancées',
                    'API access',
                    'Support dédié',
                    'Badge premium',
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

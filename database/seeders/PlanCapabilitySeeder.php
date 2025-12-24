<?php

namespace Database\Seeders;

use App\Models\CreatorPlan;
use App\Models\PlanCapability;
use Illuminate\Database\Seeder;

class PlanCapabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Injecte exactement le mapping Plan → Capability validé.
     * Aucune logique conditionnelle, juste des données pures.
     */
    public function run(): void
    {
        $freePlan = CreatorPlan::where('code', 'free')->first();
        $officialPlan = CreatorPlan::where('code', 'official')->first();
        $premiumPlan = CreatorPlan::where('code', 'premium')->first();

        if (!$freePlan || !$officialPlan || !$premiumPlan) {
            $this->command->error('Les plans doivent être créés avant les capabilities. Exécutez CreatorPlanSeeder d\'abord.');
            return;
        }

        // Mapping des capabilities par plan
        $capabilities = [
            // FREE PLAN
            'free' => [
                'can_add_products' => ['bool' => true],
                'max_products' => ['int' => 5],
                'can_manage_collections' => ['bool' => false],
                'can_view_advanced_stats' => ['bool' => false],
                'can_view_analytics' => ['bool' => false],
                'can_export_data' => ['bool' => false],
                'dashboard_layout' => ['string' => 'basic'],
                'can_use_api' => ['bool' => false],
                'max_collections' => ['int' => 0],
                'support_level' => ['string' => 'community'],
            ],
            
            // OFFICIAL PLAN
            'official' => [
                'can_add_products' => ['bool' => true],
                'max_products' => ['int' => -1], // -1 = illimité
                'can_manage_collections' => ['bool' => true],
                'can_view_advanced_stats' => ['bool' => true],
                'can_view_analytics' => ['bool' => true],
                'can_export_data' => ['bool' => true],
                'dashboard_layout' => ['string' => 'advanced'],
                'can_use_api' => ['bool' => false],
                'max_collections' => ['int' => 10],
                'support_level' => ['string' => 'priority'],
            ],
            
            // PREMIUM PLAN
            'premium' => [
                'can_add_products' => ['bool' => true],
                'max_products' => ['int' => -1], // -1 = illimité
                'can_manage_collections' => ['bool' => true],
                'can_view_advanced_stats' => ['bool' => true],
                'can_view_analytics' => ['bool' => true],
                'can_export_data' => ['bool' => true],
                'dashboard_layout' => ['string' => 'premium'],
                'can_use_api' => ['bool' => true],
                'max_collections' => ['int' => -1], // -1 = illimité
                'support_level' => ['string' => 'dedicated'],
            ],
        ];

        // Injecter les capabilities pour chaque plan
        foreach ($capabilities as $planCode => $planCaps) {
            $plan = CreatorPlan::where('code', $planCode)->first();
            
            if (!$plan) {
                continue;
            }

            foreach ($planCaps as $capabilityKey => $value) {
                PlanCapability::updateOrCreate(
                    [
                        'creator_plan_id' => $plan->id,
                        'capability_key' => $capabilityKey,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\CreatorPlan;
use App\Models\PlanCapability;
use Illuminate\Database\Seeder;

class PlanCapabilitySeeder extends Seeder
{
    public function run(): void
    {
        $atelierPlan   = CreatorPlan::where('code', 'atelier')->first();
        $maisonPlan    = CreatorPlan::where('code', 'maison')->first();
        $signaturePlan = CreatorPlan::where('code', 'signature')->first();

        if (! $atelierPlan || ! $maisonPlan || ! $signaturePlan) {
            $this->command->error('Plans atelier/maison/signature introuvables. Exécuter CreatorPlanSeeder d\'abord.');
            return;
        }

        $capabilities = [
            'atelier' => [
                'can_add_products'       => ['bool' => true],
                'max_products'           => ['int' => 80],
                'can_manage_collections' => ['bool' => false],
                'can_view_advanced_stats'=> ['bool' => false],
                'can_view_analytics'     => ['bool' => false],
                'can_export_data'        => ['bool' => false],
                'dashboard_layout'       => ['string' => 'basic'],
                'can_use_pos'            => ['bool' => false],
                'can_use_api'            => ['bool' => false],
                'support_level'          => ['string' => 'community'],
            ],
            'maison' => [
                'can_add_products'       => ['bool' => true],
                'max_products'           => ['int' => 250],
                'can_manage_collections' => ['bool' => true],
                'can_view_advanced_stats'=> ['bool' => true],
                'can_view_analytics'     => ['bool' => true],
                'can_export_data'        => ['bool' => true],
                'dashboard_layout'       => ['string' => 'advanced'],
                'can_use_pos'            => ['bool' => false],
                'can_use_api'            => ['bool' => false],
                'support_level'          => ['string' => 'priority'],
            ],
            'signature' => [
                'can_add_products'       => ['bool' => true],
                'max_products'           => ['int' => -1],
                'can_manage_collections' => ['bool' => true],
                'can_view_advanced_stats'=> ['bool' => true],
                'can_view_analytics'     => ['bool' => true],
                'can_export_data'        => ['bool' => true],
                'dashboard_layout'       => ['string' => 'premium'],
                'can_use_pos'            => ['bool' => true],
                'can_use_api'            => ['bool' => true],
                'support_level'          => ['string' => 'dedicated'],
            ],
        ];

        foreach ($capabilities as $planCode => $planCaps) {
            $plan = CreatorPlan::where('code', $planCode)->first();
            if (! $plan) {
                continue;
            }

            foreach ($planCaps as $capabilityKey => $value) {
                PlanCapability::updateOrCreate(
                    [
                        'creator_plan_id' => $plan->id,
                        'capability_key'  => $capabilityKey,
                    ],
                    ['value' => $value]
                );
            }
        }
    }
}

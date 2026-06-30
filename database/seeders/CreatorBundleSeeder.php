<?php

namespace Database\Seeders;

use App\Models\CreatorAddon;
use App\Models\CreatorBundle;
use App\Models\CreatorPlan;
use Illuminate\Database\Seeder;

class CreatorBundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * V2.4 : Bundles avec plans actuels (atelier, maison)
     */
    public function run(): void
    {
        // Correction: utiliser plans actuels (atelier, maison) au lieu de obsolètes (official, premium)
        $atelierPlan = CreatorPlan::where('code', 'atelier')->first();
        $maisonPlan = CreatorPlan::where('code', 'maison')->first();

        if (!$atelierPlan || !$maisonPlan) {
            $this->command->warn('Les plans ATELIER et MAISON doivent exister avant de créer les bundles.');
            return;
        }

        // Récupérer les IDs des add-ons
        $apiAccess = CreatorAddon::where('code', 'api_access')->first();
        $advancedAnalytics = CreatorAddon::where('code', 'advanced_analytics')->first();
        $prioritySupport = CreatorAddon::where('code', 'priority_support')->first();

        $bundles = [
            [
                'code' => 'starter_pack',
                'name' => 'Starter Pack',
                'description' => 'Plan Atelier + Accès API pour démarrer votre boutique professionnelle',
                'price' => 22500.00, // 15000 (atelier) + 10000 (api) = 25000, économie de 2500
                'base_plan_id' => $atelierPlan->id,
                'included_addon_ids' => $apiAccess ? [$apiAccess->id] : [],
                'is_active' => true,
            ],
            [
                'code' => 'pro_pack',
                'name' => 'Pro Pack',
                'description' => 'Plan Maison + API + Analytics + Support Prioritaire',
                'price' => 52500.00, // 35000 (maison) + 10000 (api) + 7500 (analytics) + 5000 (support) = 57500, économie de 5000
                'base_plan_id' => $maisonPlan->id,
                'included_addon_ids' => array_filter([
                    $apiAccess?->id,
                    $advancedAnalytics?->id,
                    $prioritySupport?->id,
                ]),
                'is_active' => true,
            ],
        ];

        foreach ($bundles as $bundleData) {
            CreatorBundle::updateOrCreate(
                ['code' => $bundleData['code']],
                $bundleData
            );
        }
    }
}

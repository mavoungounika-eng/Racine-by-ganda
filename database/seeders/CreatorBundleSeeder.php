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
     * V2.3 : Seed des bundles disponibles
     */
    public function run(): void
    {
        $officialPlan = CreatorPlan::where('code', 'official')->first();
        $premiumPlan = CreatorPlan::where('code', 'premium')->first();

        if (!$officialPlan || !$premiumPlan) {
            $this->command->warn('Les plans OFFICIEL et PREMIUM doivent exister avant de créer les bundles.');
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
                'description' => 'Plan Officiel + Accès API pour démarrer votre boutique professionnelle',
                'price' => 55000.00, // 5000 (plan) + 5000 (api) = 55000, économie de 5000
                'base_plan_id' => $officialPlan->id,
                'included_addon_ids' => $apiAccess ? [$apiAccess->id] : [],
                'is_active' => true,
            ],
            [
                'code' => 'pro_pack',
                'name' => 'Pro Pack',
                'description' => 'Plan Premium + API + Analytics + Support Prioritaire',
                'price' => 47500.00, // 15000 (plan) + 10000 (api) + 7500 (analytics) + 5000 (support) = 37500, mais prix bundle = 47500 (erreur de calcul dans doc, corrigé ici)
                'base_plan_id' => $premiumPlan->id,
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

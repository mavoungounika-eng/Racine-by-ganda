<?php

namespace Database\Seeders;

use App\Models\CreatorAddon;
use Illuminate\Database\Seeder;

class CreatorAddonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * V2.2 : Seed des add-ons disponibles
     */
    public function run(): void
    {
        $addons = [
            [
                'code' => 'api_access',
                'name' => 'Accès API',
                'description' => 'Accès complet à l\'API RACINE pour intégrations et automatisations',
                'price' => 10000.00,
                'capability_key' => 'can_use_api',
                'capability_value' => true,
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ],
            [
                'code' => 'advanced_analytics',
                'name' => 'Analytics Avancés',
                'description' => 'Analytics détaillés avec exports Excel, rapports personnalisés et insights',
                'price' => 7500.00,
                'capability_key' => 'can_view_analytics',
                'capability_value' => true,
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ],
            [
                'code' => 'priority_support',
                'name' => 'Support Prioritaire',
                'description' => 'Support prioritaire avec réponse garantie sous 4 heures',
                'price' => 5000.00,
                'capability_key' => 'support_level',
                'capability_value' => 'priority',
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ],
            [
                'code' => 'custom_domain',
                'name' => 'Domaine Personnalisé',
                'description' => 'Utiliser votre propre domaine (ex: boutique.votredomaine.com)',
                'price' => 15000.00,
                'capability_key' => 'can_customize_domain',
                'capability_value' => true,
                'billing_cycle' => 'one_time',
                'is_active' => true,
            ],
            [
                'code' => 'white_label',
                'name' => 'White Label',
                'description' => 'Suppression du branding RACINE, personnalisation complète',
                'price' => 25000.00,
                'capability_key' => 'can_white_label',
                'capability_value' => true,
                'billing_cycle' => 'monthly',
                'is_active' => true,
            ],
        ];

        foreach ($addons as $addonData) {
            CreatorAddon::updateOrCreate(
                ['code' => $addonData['code']],
                $addonData
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // ═══════════════════════════════════════
            // GROUPE : GÉNÉRAL
            // ═══════════════════════════════════════
            [
                'key' => 'site_name',
                'value' => 'RACINE BY GANDA',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nom du site',
                'description' => 'Nom affiché sur tout le site',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'site_email',
                'value' => 'contact@racinebyganda.com',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Email de contact',
                'description' => 'Email principal pour les notifications',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'site_phone',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Téléphone',
                'description' => 'Numéro de contact affiché',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'site_address',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Adresse physique',
                'description' => 'Adresse du magasin/bureau',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'site_country',
                'value' => 'Cameroun',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Pays',
                'description' => 'Pays principal d\'activité',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'site_timezone',
                'value' => 'Africa/Douala',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Fuseau horaire',
                'description' => 'Fuseau horaire du site',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'social_facebook',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Facebook',
                'description' => 'URL de la page Facebook',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'social_instagram',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Instagram',
                'description' => 'URL du profil Instagram',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'social_twitter',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Twitter/X',
                'description' => 'URL du profil Twitter/X',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'social_whatsapp',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'WhatsApp Business',
                'description' => 'Numéro WhatsApp Business',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'social_tiktok',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'TikTok',
                'description' => 'URL du profil TikTok',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'social_linkedin',
                'value' => '',
                'type' => 'string',
                'group' => 'general',
                'label' => 'LinkedIn',
                'description' => 'URL de la page LinkedIn',
                'is_encrypted' => false,
                'is_public' => true,
            ],

            // ═══════════════════════════════════════
            // GROUPE : MARKETPLACE
            // ═══════════════════════════════════════
            [
                'key' => 'commission_rate',
                'value' => '15.00',
                'type' => 'float',
                'group' => 'marketplace',
                'label' => 'Taux de commission par défaut (%)',
                'description' => 'Commission prélevée sur les ventes des créateurs marketplace',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'shipping_fee',
                'value' => '2000',
                'type' => 'integer',
                'group' => 'marketplace',
                'label' => 'Frais de livraison par défaut (FCFA)',
                'description' => 'Frais de livraison standard',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'currency',
                'value' => 'FCFA',
                'type' => 'string',
                'group' => 'marketplace',
                'label' => 'Devise',
                'description' => 'Devise principale du site',
                'is_encrypted' => false,
                'is_public' => true,
            ],
            [
                'key' => 'low_stock_threshold',
                'value' => '10',
                'type' => 'integer',
                'group' => 'marketplace',
                'label' => 'Seuil stock faible',
                'description' => 'Alerte si stock inférieur à cette valeur',
                'is_encrypted' => false,
                'is_public' => false,
            ],

            // ═══════════════════════════════════════
            // GROUPE : PAYMENTS
            // ═══════════════════════════════════════
            [
                'key' => 'stripe_mode',
                'value' => 'test',
                'type' => 'string',
                'group' => 'payments',
                'label' => 'Mode Stripe',
                'description' => 'Mode test ou production pour Stripe',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'payments_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'payments',
                'label' => 'Paiements en ligne activés',
                'description' => 'Autoriser ou bloquer les paiements en ligne',
                'is_encrypted' => false,
                'is_public' => false,
            ],

            // ═══════════════════════════════════════
            // GROUPE : ADVANCED
            // ═══════════════════════════════════════
            [
                'key' => 'registrations_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'advanced',
                'label' => 'Inscriptions publiques activées',
                'description' => 'Autoriser les nouvelles inscriptions sur le site',
                'is_encrypted' => false,
                'is_public' => false,
            ],
            [
                'key' => 'maintenance_message',
                'value' => '',
                'type' => 'string',
                'group' => 'advanced',
                'label' => 'Message de maintenance',
                'description' => 'Message affiché pendant la maintenance',
                'is_encrypted' => false,
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                ['key' => $setting['key']],
                array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $this->command->info('✅ ' . count($settings) . ' settings insérés/mis à jour');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // IMPORTANT: Les rôles doivent être créés en premier
        // car les utilisateurs ont une clé étrangère vers roles.id
        $this->call([
            RolesTableSeeder::class,
            CmsPagesSeeder::class,
            CmsSectionsSeeder::class,
            PaymentProviderSeeder::class,
            PaymentRoutingRuleSeeder::class,
            // Seeders pour le système d'abonnement créateur
            CreatorPlanSeeder::class,
            PlanCapabilitySeeder::class,
            // V2 : Seeders pour add-ons et bundles
            CreatorAddonSeeder::class,
            CreatorBundleSeeder::class, // Corrigé: utilise plans atelier/maison
        ]);

        // ====================================
        // SEEDERS DEMO (LOCAL/TESTING UNIQUEMENT)
        // ====================================
        if (app()->environment('local', 'testing')) {
            $this->call([
                TestUsersSeeder::class,
            ]);
        }

        /**
         * SUPER ADMINISTRATEUR PAR DÉFAUT
         * 
         * Cet utilisateur a les privilèges maximum :
         * - is_admin = true (flag legacy)
         * - role_id = 1 (correspond au rôle 'super_admin')
         * 
         * Identifiants par défaut :
         * - Email: admin@racine.com
         * - Password: admin123
         * 
         * ⚠️ IMPORTANT: Changez le mot de passe en production !
         */
        User::updateOrCreate(
            ['email' => 'admin@racine.com'],
            [
                'name' => 'Super Administrateur',
                'email' => 'admin@racine.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true, // Flag legacy pour rétro-compatibilité
                'role_id' => 1, // ID du rôle 'super_admin' (créé dans RolesTableSeeder)
                'status' => 'active',
                'email_verified_at' => now(),
                // Désactiver la 2FA en développement
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_required' => false,
            ]
        );

        // Compte admin production
        User::updateOrCreate(
            ['email' => 'admin@racinebyganda.com'],
            [
                'name' => 'Administrateur Racine',
                'email' => 'admin@racinebyganda.com',
                'password' => Hash::make('Admin@2026!'),
                'is_admin' => true,
                'role_id' => 1,
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_required' => false,
            ]
        );

        // TODO PROD: Changer le mot de passe admin@racine.com et admin@racinebyganda.com
        // TODO PROD: Activer two_factor_required = true pour admin@racinebyganda.com
    }
}

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
            CreatorBundleSeeder::class,
        ]);

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

        /**
         * COMPTE DÉVELOPPEUR PASSE-PARTOUT
         * 
         * Compte de développement avec accès complet
         * 
         * Identifiants :
         * - Email: dev@racine.com
         * - Password: dev123
         */
        User::updateOrCreate(
            ['email' => 'dev@racine.com'],
            [
                'name' => 'Developer',
                'email' => 'dev@racine.com',
                'password' => Hash::make('dev123'),
                'is_admin' => true,
                'role_id' => 1, // Super admin
                'status' => 'active',
                'email_verified_at' => now(),
                // Désactiver la 2FA en développement
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_required' => false,
            ]
        );

        // Créer un utilisateur de test (non admin)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'role_id' => 4, // ID du rôle 'client'
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Optionnel : créer des utilisateurs via factory
        // User::factory(10)->create();
    }
}

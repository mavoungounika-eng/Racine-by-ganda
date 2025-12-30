<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // IMPORTANT: S'assurer que les rôles existent
        $this->call(RolesTableSeeder::class);
        
        // Supprimer tous les anciens comptes de test
        $this->deleteOldTestAccounts();
        
        // 1. SUPER ADMINISTRATEUR
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@racine.cm'],
            [
                'name' => 'Super Admin RACINE',
                'password' => Hash::make('password'),
                'role_id' => 1,
                'role' => 'super_admin',
                'is_admin' => true,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]
        );
        echo "✅ Super Admin créé : {$superAdmin->email}\n";

        // 2. ADMINISTRATEUR
        $admin = User::updateOrCreate(
            ['email' => 'admin@racine.cm'],
            [
                'name' => 'Admin RACINE',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'role' => 'admin',
                'is_admin' => true,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ]
        );
        echo "✅ Admin créé : {$admin->email}\n";

        // 3. STAFF GÉNÉRAL
        $staff = User::updateOrCreate(
            ['email' => 'staff@racine.cm'],
            [
                'name' => 'Staff RACINE',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'role' => 'staff',
                'staff_role' => null,
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        echo "✅ Staff créé : {$staff->email}\n";

        // 4. STAFF VENDEUR
        $vendeur = User::updateOrCreate(
            ['email' => 'vendeur@racine.cm'],
            [
                'name' => 'Vendeur RACINE',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'role' => 'staff',
                'staff_role' => 'vendeur',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        echo "✅ Staff Vendeur créé : {$vendeur->email}\n";

        // 5. STAFF CAISSIER
        $caissier = User::updateOrCreate(
            ['email' => 'caissier@racine.cm'],
            [
                'name' => 'Caissier RACINE',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'role' => 'staff',
                'staff_role' => 'caissier',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        echo "✅ Staff Caissier créé : {$caissier->email}\n";

        // 6. STAFF GESTIONNAIRE STOCK
        $stock = User::updateOrCreate(
            ['email' => 'stock@racine.cm'],
            [
                'name' => 'Gestionnaire Stock RACINE',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'role' => 'staff',
                'staff_role' => 'gestionnaire_stock',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        echo "✅ Staff Gestionnaire Stock créé : {$stock->email}\n";

        // 7. STAFF COMPTABLE
        $comptable = User::updateOrCreate(
            ['email' => 'comptable@racine.cm'],
            [
                'name' => 'Comptable RACINE',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'role' => 'staff',
                'staff_role' => 'comptable',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        echo "✅ Staff Comptable créé : {$comptable->email}\n";

        // 8. CRÉATEUR ACTIF
        $createur = User::updateOrCreate(
            ['email' => 'createur@racine.cm'],
            [
                'name' => 'Créateur Test',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'role' => 'createur',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        
        // Créer le profil créateur actif
        CreatorProfile::updateOrCreate(
            ['user_id' => $createur->id],
            [
                'brand_name' => 'Boutique Test Créateur',
                'slug' => 'boutique-test-createur',
                'bio' => 'Créateur de test avec compte actif',
                'status' => 'active',
                'is_verified' => true,
                'is_active' => true,
            ]
        );
        echo "✅ Créateur actif créé : {$createur->email}\n";

        // 9. CRÉATEUR EN ATTENTE
        $createurPending = User::updateOrCreate(
            ['email' => 'createur.pending@racine.cm'],
            [
                'name' => 'Créateur Pending',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'role' => 'createur',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        
        // Créer le profil créateur en attente
        CreatorProfile::updateOrCreate(
            ['user_id' => $createurPending->id],
            [
                'brand_name' => 'Boutique Pending',
                'slug' => 'boutique-pending',
                'bio' => 'Créateur en attente de validation',
                'status' => 'pending',
                'is_verified' => false,
                'is_active' => false,
            ]
        );
        echo "✅ Créateur pending créé : {$createurPending->email}\n";

        // 10. CRÉATEUR SUSPENDU
        $createurSuspended = User::updateOrCreate(
            ['email' => 'createur.suspended@racine.cm'],
            [
                'name' => 'Créateur Suspended',
                'password' => Hash::make('password'),
                'role_id' => 4,
                'role' => 'createur',
                'is_admin' => false,
                'phone' => '+237 6XX XXX XXX',
                'status' => 'active',
                'email_verified_at' => now(),
                'two_factor_required' => false,
            ]
        );
        
        // Créer le profil créateur suspendu
        CreatorProfile::updateOrCreate(
            ['user_id' => $createurSuspended->id],
            [
                'brand_name' => 'Boutique Suspended',
                'slug' => 'boutique-suspended',
                'bio' => 'Créateur avec compte suspendu',
                'status' => 'suspended',
                'is_verified' => false,
                'is_active' => false,
            ]
        );
        echo "✅ Créateur suspended créé : {$createurSuspended->email}\n";

        // 11. CLIENTS
        $clients = [
            [
                'email' => 'client@racine.cm',
                'name' => 'Client Test 1',
            ],
            [
                'email' => 'client2@racine.cm',
                'name' => 'Client Test 2',
            ],
            [
                'email' => 'client3@racine.cm',
                'name' => 'Client Test 3',
            ],
        ];

        foreach ($clients as $clientData) {
            $client = User::updateOrCreate(
                ['email' => $clientData['email']],
                [
                    'name' => $clientData['name'],
                    'password' => Hash::make('password'),
                    'role_id' => 5,
                    'role' => 'client',
                    'is_admin' => false,
                    'phone' => '+237 6XX XXX XXX',
                    'status' => 'active',
                    'email_verified_at' => now(),
                    'two_factor_required' => false,
                ]
            );
            echo "✅ Client créé : {$client->email}\n";
        }

        echo "\n🎉 Tous les comptes de test ont été créés avec succès !\n";
        echo "📝 Mot de passe pour tous les comptes : password\n";
    }

    /**
     * Supprimer tous les anciens comptes de test.
     */
    private function deleteOldTestAccounts(): void
    {
        $testEmails = [
            'superadmin@racine.cm',
            'admin@racine.cm',
            'staff@racine.cm',
            'vendeur@racine.cm',
            'caissier@racine.cm',
            'stock@racine.cm',
            'comptable@racine.cm',
            'createur@racine.cm',
            'createur.pending@racine.cm',
            'createur.suspended@racine.cm',
            'client@racine.cm',
            'client2@racine.cm',
            'client3@racine.cm',
            // Anciens comptes possibles
            'test@racine.cm',
            'demo@racine.cm',
            'test.admin@racine.cm',
            'test.createur@racine.cm',
            'test.client@racine.cm',
        ];

        // Supprimer les profils créateurs associés
        $oldUsers = User::whereIn('email', $testEmails)->get();
        foreach ($oldUsers as $user) {
            if ($user->creatorProfile) {
                $user->creatorProfile->delete();
            }
        }

        // Supprimer les utilisateurs
        $deleted = User::whereIn('email', $testEmails)->delete();
        
        if ($deleted > 0) {
            echo "🗑️  {$deleted} ancien(s) compte(s) de test supprimé(s)\n";
        }
    }
}


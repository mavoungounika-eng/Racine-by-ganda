<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BrandUserSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Vérifier si l'utilisateur brand existe déjà
        $brandUser = User::where('email', 'brand@racinebyganda.com')->first();

        if ($brandUser) {
            $this->command->info('✅ L\'utilisateur RACINE BY GANDA existe déjà (ID: ' . $brandUser->id . ')');
            return;
        }

        // Trouver ou créer le rôle admin
        $adminRole = Role::where('slug', 'admin')->first();
        
        if (!$adminRole) {
            $adminRole = Role::where('slug', 'super_admin')->first();
        }

        // Créer l'utilisateur brand
        $brandUser = User::create([
            'name' => 'RACINE BY GANDA',
            'email' => 'brand@racinebyganda.com',
            'password' => Hash::make('RacineBrand2025!SecurePassword'), // Mot de passe sécurisé
            'role_id' => $adminRole?->id ?? 1,
            'role' => 'admin',
            'is_admin' => true,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->command->info('✅ Utilisateur RACINE BY GANDA créé avec succès !');
        $this->command->info('   - ID: ' . $brandUser->id);
        $this->command->info('   - Email: brand@racinebyganda.com');
        $this->command->info('   - Rôle: Admin');
        $this->command->warn('   ⚠️  Mot de passe: RacineBrand2025!SecurePassword (à changer en production)');
    }
}

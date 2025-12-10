<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = 'dev@racine.com';
        $password = 'dev123';
        $name = 'Developer';

        // Récupérer le rôle admin
        $adminRole = Role::where('slug', 'admin')
            ->orWhere('slug', 'super_admin')
            ->orWhere('id', 1)
            ->first();

        // Chercher ou créer le compte développeur
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_admin' => true,
                'role_id' => $adminRole ? $adminRole->id : 1,
                'status' => 'active',
            ]
        );

        $this->command->info("✅ Compte développeur créé/mis à jour :");
        $this->command->line("   Email: {$email}");
        $this->command->line("   Password: {$password}");
        $this->command->line("   URL: http://localhost:8000/admin/login");
    }
}


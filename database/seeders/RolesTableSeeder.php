<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'name' => 'Super Administrateur',
                'slug' => 'super_admin',
                'description' => 'Accès complet à toutes les fonctionnalités du système. Peut gérer les autres administrateurs.',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'name' => 'Administrateur',
                'slug' => 'admin',
                'description' => 'Accès administrateur standard. Peut gérer les utilisateurs et le contenu.',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'name' => 'Staff',
                'slug' => 'staff',
                'description' => 'Membre de l\'équipe avec accès aux outils internes.',
                'is_active' => true,
            ],
            [
                'id' => 4,
                'name' => 'Créateur',
                'slug' => 'createur',
                'description' => 'Créateur/Designer partenaire. Peut gérer ses produits et sa boutique.',
                'is_active' => true,
            ],
            [
                'id' => 5,
                'name' => 'Client',
                'slug' => 'client',
                'description' => 'Client standard avec accès aux commandes et au profil.',
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['id' => $role['id']],
                $role
            );
        }
    }
}


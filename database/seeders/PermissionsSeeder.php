<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Produits
            ['slug' => 'view-products', 'name' => 'Voir Produits', 'category' => 'products'],
            ['slug' => 'create-products', 'name' => 'Créer Produits', 'category' => 'products'],
            ['slug' => 'edit-products', 'name' => 'Modifier Produits', 'category' => 'products'],
            ['slug' => 'delete-products', 'name' => 'Supprimer Produits', 'category' => 'products'],
            
            // Commandes
            ['slug' => 'view-orders', 'name' => 'Voir Commandes', 'category' => 'orders'],
            ['slug' => 'view-all-orders', 'name' => 'Voir Toutes Commandes', 'category' => 'orders'],
            ['slug' => 'edit-orders', 'name' => 'Modifier Commandes', 'category' => 'orders'],
            ['slug' => 'delete-orders', 'name' => 'Supprimer Commandes', 'category' => 'orders'],
            ['slug' => 'process-payments', 'name' => 'Traiter Paiements', 'category' => 'orders'],
            
            // Stock
            ['slug' => 'view-stock', 'name' => 'Voir Stock', 'category' => 'stock'],
            ['slug' => 'edit-stock', 'name' => 'Modifier Stock', 'category' => 'stock'],
            ['slug' => 'view-stock-analytics', 'name' => 'Voir Analytics Stock', 'category' => 'analytics'],
            
            // Analytics
            ['slug' => 'view-sales-analytics', 'name' => 'Voir Analytics Ventes', 'category' => 'analytics'],
            
            // Utilisateurs
            ['slug' => 'view-users', 'name' => 'Voir Utilisateurs', 'category' => 'users'],
            ['slug' => 'create-users', 'name' => 'Créer Utilisateurs', 'category' => 'users'],
            ['slug' => 'edit-users', 'name' => 'Modifier Utilisateurs', 'category' => 'users'],
            ['slug' => 'delete-users', 'name' => 'Supprimer Utilisateurs', 'category' => 'users'],
            
            // Catégories
            ['slug' => 'view-categories', 'name' => 'Voir Catégories', 'category' => 'categories'],
            ['slug' => 'create-categories', 'name' => 'Créer Catégories', 'category' => 'categories'],
            ['slug' => 'edit-categories', 'name' => 'Modifier Catégories', 'category' => 'categories'],
            ['slug' => 'delete-categories', 'name' => 'Supprimer Catégories', 'category' => 'categories'],
            
            // Système
            ['slug' => 'manage-settings', 'name' => 'Gérer Paramètres', 'category' => 'system'],
            ['slug' => 'access-system-config', 'name' => 'Configuration Système', 'category' => 'system'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->command->info('✅ ' . count($permissions) . ' permissions créées');
    }
}

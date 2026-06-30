<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            'vendeur' => [
                'view-products',
                'view-orders',
            ],
            
            'caissier' => [
                'view-orders',
                'view-all-orders',
                'process-payments',
            ],
            
            'gestionnaire_stock' => [
                'view-products',
                'edit-products',
                'view-stock',
                'edit-stock',
                'view-stock-analytics',
            ],
            
            'moderator' => [
                'view-products',
                'create-products',
                'edit-products',
                'view-orders',
                'view-all-orders',
                'edit-orders',
                'view-categories',
                'create-categories',
                'edit-categories',
            ],
            
            'admin' => [
                'view-products',
                'create-products',
                'edit-products',
                'delete-products',
                'view-orders',
                'view-all-orders',
                'edit-orders',
                'delete-orders',
                'process-payments',
                'view-stock',
                'edit-stock',
                'view-stock-analytics',
                'view-sales-analytics',
                'view-users',
                'create-users',
                'edit-users',
                'delete-users',
                'view-categories',
                'create-categories',
                'edit-categories',
                'delete-categories',
                'manage-settings',
            ],
            
            // super_admin : bypass via Gate::before, pas de permissions explicites
        ];

        foreach ($mappings as $roleSlug => $permissionSlugs) {
            $role = Role::where('slug', $roleSlug)->first();
            
            if (!$role) {
                $this->command->warn("⚠️  Rôle '{$roleSlug}' non trouvé, ignoré");
                continue;
            }

            $permissions = Permission::whereIn('slug', $permissionSlugs)->get();
            $role->permissions()->sync($permissions);
            
            $this->command->info("✅ {$role->name} : " . $permissions->count() . " permissions");
        }
    }
}

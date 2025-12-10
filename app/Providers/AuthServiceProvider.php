<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
        Order::class => OrderPolicy::class,
        User::class => UserPolicy::class,
        Category::class => CategoryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Enregistrer les policies
        $this->registerPolicies();

        // Gates personnalisés pour permissions granulaires
        
        // Products
        Gate::define('view-products', function (User $user) {
            return true; // Tous peuvent voir
        });

        Gate::define('create-products', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('edit-products', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('delete-products', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        // Orders
        Gate::define('view-orders', function (User $user) {
            return true; // Tous peuvent voir leurs commandes
        });

        Gate::define('view-all-orders', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('edit-orders', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('delete-orders', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        // Users
        Gate::define('view-users', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('create-users', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        Gate::define('edit-users', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        Gate::define('delete-users', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        // Categories
        Gate::define('view-categories', function (User $user) {
            return true; // Tous peuvent voir
        });

        Gate::define('create-categories', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('edit-categories', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('delete-categories', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        // Dashboard & Analytics
        Gate::define('view-dashboard', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
        });

        Gate::define('view-analytics', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        // Settings
        Gate::define('manage-settings', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['admin', 'super_admin']);
        });

        // =============================================
        // GATES DASHBOARDS PAR RÔLE (Phase 2)
        // =============================================
        
        Gate::define('access-super-admin', function (User $user) {
            return $user->getRoleSlug() === 'super_admin';
        });

        Gate::define('access-admin', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['super_admin', 'admin']);
        });

        Gate::define('access-staff', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['super_admin', 'admin', 'staff']);
        });

        Gate::define('access-createur', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['super_admin', 'admin', 'createur', 'creator']);
        });

        Gate::define('access-client', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, ['super_admin', 'admin', 'staff', 'createur', 'creator', 'client']);
        });

        // =============================================
        // GATES ERP (Phase 7)
        // =============================================
        
        Gate::define('access-erp', function (User $user) {
            // Charger la relation si elle n'est pas déjà chargée
            if (!$user->relationLoaded('roleRelation')) {
                $user->load('roleRelation');
            }
            $role = $user->getRoleSlug();
            return in_array($role, ['super_admin', 'admin', 'staff']);
        });

        Gate::define('manage-erp', function (User $user) {
            // Charger la relation si elle n'est pas déjà chargée
            if (!$user->relationLoaded('roleRelation')) {
                $user->load('roleRelation');
            }
            $role = $user->getRoleSlug();
            return in_array($role, ['super_admin', 'admin']);
        });

        // =============================================
        // GATES CRM (Phase 7)
        // =============================================
        
        Gate::define('access-crm', function (User $user) {
            $role = $user->getRoleSlug();
            return in_array($role, ['super_admin', 'admin', 'staff']);
        });

        Gate::define('manage-crm', function (User $user) {
            $role = $user->getRoleSlug();
            return in_array($role, ['super_admin', 'admin']);
        });

        // Super Admin - toutes permissions
        Gate::before(function (User $user, string $ability) {
            if ($user->getRoleSlug() === 'super_admin') {
                return true; // Super Admin a tous les droits
            }
        });
    }
}

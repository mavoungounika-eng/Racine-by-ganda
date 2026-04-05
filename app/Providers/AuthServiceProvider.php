<?php

namespace App\Providers;

use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\CreatorProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Role;
use App\Models\Category;
use App\Models\Conversation;
use App\Policies\CategoryPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CreatorStripeAccountPolicy;
use App\Policies\CreatorSubscriptionPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

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
        Conversation::class => ConversationPolicy::class,
        CreatorStripeAccount::class => CreatorStripeAccountPolicy::class,
        CreatorSubscription::class => CreatorSubscriptionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Enregistrer les policies
        $this->registerPolicies();

        // Enforce strong password policies globally
        Password::defaults(function () {
            $rule = Password::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols();

            return app()->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });

        // =============================================
        // GATES RBAC - PERMISSIONS ONLY
        // =============================================
        // RÈGLE : 1 gate = 1 permission
        // AUCUNE logique rôle autorisée
        // =============================================
        
        // Products
        Gate::define('view-products', fn(User $u) => $u->hasPermission('view-products'));
        
        Gate::define('create-products', function (User $user) {
            // Un créateur doit avoir une organisation active pour créer un produit
            if ($user->isCreator()) {
                $context = app(\App\Services\Auth\UserContextResolver::class)->getFromSession();
                return $context && $context->activeCreatorId && $user->hasPermission('create-products');
            }
            return $user->hasPermission('create-products');
        });

        Gate::define('edit-products', fn(User $u) => $u->hasPermission('edit-products'));
        Gate::define('delete-products', fn(User $u) => $u->hasPermission('delete-products'));

        // Orders
        Gate::define('view-orders', fn(User $u) => $u->hasPermission('view-orders'));
        Gate::define('view-all-orders', fn(User $u) => $u->hasPermission('view-all-orders'));
        Gate::define('edit-orders', fn(User $u) => $u->hasPermission('edit-orders'));
        Gate::define('delete-orders', fn(User $u) => $u->hasPermission('delete-orders'));

        // Users
        Gate::define('view-users', fn(User $u) => $u->hasPermission('view-users'));
        Gate::define('create-users', fn(User $u) => $u->hasPermission('create-users'));
        Gate::define('edit-users', fn(User $u) => $u->hasPermission('edit-users'));
        Gate::define('delete-users', fn(User $u) => $u->hasPermission('delete-users'));

        // Categories
        Gate::define('view-categories', fn(User $u) => $u->hasPermission('view-categories'));
        Gate::define('create-categories', fn(User $u) => $u->hasPermission('create-categories'));
        Gate::define('edit-categories', fn(User $u) => $u->hasPermission('edit-categories'));
        Gate::define('delete-categories', fn(User $u) => $u->hasPermission('delete-categories'));

        // Dashboard & Analytics
        Gate::define('view-dashboard', fn(User $u) => $u->hasPermission('view-all-orders'));
        Gate::define('view-analytics', fn(User $u) => $u->hasPermission('view-sales-analytics'));
        Gate::define('view-sales-analytics', fn(User $u) => $u->hasPermission('view-sales-analytics'));
        Gate::define('view-stock-analytics', fn(User $u) => $u->hasPermission('view-stock-analytics'));

        // Settings
        Gate::define('manage-settings', fn(User $u) => $u->hasPermission('manage-settings'));

        // Stock
        Gate::define('view-stock', fn(User $u) => $u->hasPermission('view-stock'));
        Gate::define('edit-stock', fn(User $u) => $u->hasPermission('edit-stock'));

        // Payments
        Gate::define('process-payments', fn(User $u) => $u->hasPermission('process-payments'));
        Gate::define('payments.view', fn(User $u) => $u->hasPermission('view-orders'));
        Gate::define('payments.config', fn(User $u) => $u->hasPermission('manage-settings'));
        Gate::define('payments.reprocess', fn(User $u) => $u->hasPermission('process-payments'));
        Gate::define('payments.refund', fn(User $u) => $u->hasPermission('process-payments'));

        // System
        Gate::define('access-system-config', fn(User $u) => $u->hasPermission('access-system-config'));

        // =============================================
        // GATES NAVIGATION (MAPPING PERMISSIONS)
        // =============================================
        Gate::define('access-admin', fn(User $u) => $u->hasPermission('view-users'));
        Gate::define('access-staff', fn(User $u) => $u->hasPermission('view-all-orders'));
        Gate::define('access-staff-tools', fn(User $u) => $u->hasPermission('view-all-orders'));
        Gate::define('access-erp', fn(User $u) => $u->hasPermission('view-stock'));
        Gate::define('manage-erp', fn(User $u) => $u->hasPermission('edit-stock'));
        Gate::define('access-crm', fn(User $u) => $u->hasPermission('view-users'));
        Gate::define('manage-crm', fn(User $u) => $u->hasPermission('edit-users'));

        // =============================================
        // GATES HORS RBAC STAFF (NE PAS MODIFIER)
        // =============================================
        Gate::define('access-super-admin', function (User $user) {
            return $user->getRoleSlug() === Role::SUPER_ADMIN;
        });

        Gate::define('access-createur', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, [
                Role::SUPER_ADMIN,
                Role::ADMIN,
                Role::CREATEUR,
            ]);
        });

        Gate::define('access-client', function (User $user) {
            $roleSlug = $user->getRoleSlug();
            return in_array($roleSlug, [
                Role::SUPER_ADMIN,
                Role::ADMIN,
                Role::STAFF,
                Role::CREATEUR,
                Role::CLIENT,
            ]);
        });

        // =============================================
        // GATES MULTI-ACCOUNT (TEAM)
        // =============================================
        Gate::define('view-team', function (User $user, CreatorProfile $creator) {
            return $user->memberships()
                ->where('creator_profile_id', $creator->id)
                ->whereIn('role', ['owner', 'admin', 'editor', 'viewer'])
                ->exists() || ($user->creatorProfile && $user->creatorProfile->id === $creator->id);
        });

        Gate::define('manage-team', function (User $user, CreatorProfile $creator) {
            return $user->memberships()
                ->where('creator_profile_id', $creator->id)
                ->whereIn('role', ['owner', 'admin'])
                ->exists() || ($user->creatorProfile && $user->creatorProfile->id === $creator->id);
        });

        // =============================================
        // SUPER ADMIN BYPASS (CRITIQUE)
        // =============================================
        Gate::before(function (User $user, string $ability) {
            if ($user->getRoleSlug() === Role::SUPER_ADMIN) {
                return true; // Super Admin a tous les droits
            }
        });
    }
}

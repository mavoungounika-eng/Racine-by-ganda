<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\Product;
use App\Models\CreatorProfile;
use App\Models\CreatorDocument;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Observers\CreatorProfileObserver;
use App\Observers\CreatorDocumentObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer NotificationService comme singleton
        $this->app->singleton(\App\Services\NotificationService::class);
        
        // Enregistrer CmsContentService comme singleton
        $this->app->singleton(\App\Services\CmsContentService::class);
        
        // Enregistrer CmsCacheService comme singleton
        $this->app->singleton(\Modules\CMS\Services\CmsCacheService::class);
        
        // Enregistrer ProductCodeService et OrderNumberService comme singleton
        $this->app->singleton(\App\Services\ProductCodeService::class);
        $this->app->singleton(\App\Services\OrderNumberService::class);
        
        // Enregistrer DashboardCacheService comme singleton
        $this->app->singleton(\App\Services\DashboardCacheService::class);
        
        // Enregistrer CreatorNotificationService comme singleton
        $this->app->singleton(\App\Services\CreatorNotificationService::class);
        
        // Enregistrer CreatorScoringService comme singleton
        $this->app->singleton(\App\Services\CreatorScoringService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer les Observers pour les notifications automatiques
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        CreatorProfile::observe(CreatorProfileObserver::class);
        CreatorDocument::observe(CreatorDocumentObserver::class);

        // Gates pour les permissions par rôle
        Gate::define('access-super-admin', function ($user) {
            return $user->hasRole('super_admin');
        });

        Gate::define('access-admin', function ($user) {
            return in_array($user->role, ['super_admin', 'admin']);
        });

        Gate::define('access-staff', function ($user) {
            return in_array($user->role, ['super_admin', 'admin', 'staff']);
        });

        Gate::define('access-createur', function ($user) {
            return $user->hasRole('createur');
        });

        Gate::define('access-client', function ($user) {
            return $user->hasRole('client');
        });

        // Gate access-crm (access-erp est défini dans AuthServiceProvider)
        Gate::define('access-crm', function ($user) {
            return in_array($user->role, ['super_admin', 'admin', 'staff']);
        });

        // Note: access-erp est défini dans AuthServiceProvider pour éviter les doublons
    }
}

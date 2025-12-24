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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        
        // Enregistrer CreatorCapabilityService comme singleton
        $this->app->singleton(\App\Services\CreatorCapabilityService::class);
        
        // Enregistrer SubscriptionAnalyticsService comme singleton
        $this->app->singleton(\App\Services\SubscriptionAnalyticsService::class);
        
        // V2.2 : Enregistrer CreatorAddonService comme singleton
        $this->app->singleton(\App\Services\CreatorAddonService::class);
        
        // V2.3 : Enregistrer CreatorBundleService comme singleton
        $this->app->singleton(\App\Services\CreatorBundleService::class);
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

        // Définir le rate limiter 'api' pour les webhooks
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Définir le rate limiter 'webhooks' pour les endpoints webhooks (anti-abus)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // ⚠️ DOUBLONS SUPPRIMÉS : Ces Gates sont déjà définis dans AuthServiceProvider
        // avec une logique plus complète utilisant getRoleSlug().
        // 
        // Les Gates suivants sont définis dans AuthServiceProvider :
        // - access-super-admin
        // - access-admin
        // - access-staff
        // - access-createur
        // - access-client
        // - access-crm
        // - access-erp
        // - manage-erp
        // - manage-crm
        //
        // Ne pas redéfinir ici pour éviter les conflits et incohérences.
    }
}

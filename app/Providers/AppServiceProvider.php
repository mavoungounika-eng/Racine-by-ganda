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
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Observers\ErpPurchaseObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

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
        
        // Phase 3 : Queue Protection Services
        $this->app->singleton(\App\Services\Queue\QueueCircuitBreaker::class);
        $this->app->singleton(\App\Services\Queue\QueueRateLimiter::class);
        $this->app->singleton(\App\Services\Queue\QueueMonitor::class);
        
        // Phase 3 : Monitoring & Alerts
        $this->app->singleton(\App\Services\Monitoring\AlertService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // =====================================================
        // TASK 11 — Security Hardening: Password Policy
        // =====================================================
        // Politique de mots de passe globale (appliquée via Password::defaults())
        // Utilisée dans tous les validators avec la règle Password::defaults()
        Password::defaults(function () {
            return Password::min(12)
                ->mixedCase()    // Au moins 1 majuscule + 1 minuscule
                ->numbers()      // Au moins 1 chiffre
                ->symbols()      // Au moins 1 caractère spécial
                ->uncompromised(); // Vérification HaveIBeenPwned
        });

        // Enregistrer les Observers pour les notifications automatiques
        Order::observe(OrderObserver::class);
        Product::observe(ProductObserver::class);
        CreatorProfile::observe(CreatorProfileObserver::class);
        CreatorDocument::observe(CreatorDocumentObserver::class);
        ErpPurchase::observe(ErpPurchaseObserver::class);

        // Définir le rate limiter 'api' pour les webhooks
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
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
        
        // Phase 3 : Configuration Sentry contexte utilisateur
        if (app()->bound('sentry') && config('sentry.dsn')) {
            \Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
                // User context
                if (auth()->check()) {
                    $user = auth()->user();
                    $scope->setUser([
                        'id' => $user->id,
                        'email' => $user->email,
                        'role' => $user->role,
                        'username' => $user->name,
                    ]);
                }
                
                // Tags
                $scope->setTag('environment', config('app.env'));
                $scope->setTag('app_version', config('app.version', '1.0.0'));
                $scope->setTag('laravel_version', app()->version());
                
                // Context
                $scope->setContext('app', [
                    'name' => config('app.name'),
                    'url' => config('app.url'),
                    'timezone' => config('app.timezone'),
                ]);
            });
        }
    }
}

<?php

namespace App\Providers;

use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Partager le compteur de panier avec toutes les vues
        View::composer('*', function ($view) {
            $cartService = Auth::check() 
                ? new DatabaseCartService() 
                : new SessionCartService();
            
            $cartCount = $cartService->count();
            
            $view->with('cartCount', $cartCount);
        });
        
        // Composer global pour la navigation (backUrl et breadcrumbs)
        View::composer('*', \App\Http\View\Composers\NavigationComposer::class);

        // Charger creatorProfile pour toutes les vues créateur
        View::composer(['layouts.creator', 'creator.*'], function ($view) {
            if (Auth::check() && method_exists(Auth::user(), 'isCreator') && Auth::user()->isCreator()) {
                Auth::user()->loadMissing('creatorProfile');
            }
        });
    }
}

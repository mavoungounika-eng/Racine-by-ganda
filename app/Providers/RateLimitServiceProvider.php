<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
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
        // Rate limiting pour checkout (10 requêtes par minute)
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Trop de tentatives. Veuillez réessayer dans quelques instants.'
                    ], 429);
                });
        });

        // Rate limiting pour webhooks (100 requêtes par minute par IP)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(100)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'error' => 'Rate limit exceeded'
                    ], 429);
                });
        });

        // Rate limiting pour API checkout (vérification stock, etc.)
        RateLimiter::for('api-checkout', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}

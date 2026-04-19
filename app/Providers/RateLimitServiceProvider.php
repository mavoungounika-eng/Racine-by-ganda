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

        // Rate limiting pour webhooks (60 requêtes par minute par IP)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(60)
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

        // Rate limiting par device POS
        RateLimiter::for('pos_device', function (Request $request) {
            $deviceId = $request->posDevice?->id ?? $request->ip();

            return Limit::perMinute(300)->by('pos_device_' . $deviceId);
        });

        // Rate limiting pour login opérateur POS.
        // 20/min par IP : laisse passer les miss-clics et la saisie itérative
        // de dev, tout en bloquant un brute-force naïf. Retry-After est ajouté
        // automatiquement par Laravel et la réponse JSON est i18n-friendly.
        RateLimiter::for('pos_operator_login', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    $retryAfter = (int) ($headers['Retry-After'] ?? 60);

                    return response()->json([
                        'success' => false,
                        'error' => [
                            'code' => 'TOO_MANY_ATTEMPTS',
                            'message' => 'Trop de tentatives de connexion. Réessayez dans ' . $retryAfter . ' seconde(s).',
                            'retry_after' => $retryAfter,
                        ],
                    ], 429, $headers);
                });
        });
    }
}

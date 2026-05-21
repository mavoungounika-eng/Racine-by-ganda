<?php

use App\Exceptions\PosException;
use App\Http\Responses\PosApiResponse;
use Illuminate\Foundation\Application;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
        \App\Providers\RateLimitServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        then: function () {
            // POS Routes (Audit-Ready Architecture)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/pos.php'));

            // POS API Routes (Device JWT)
            \Illuminate\Support\Facades\Route::middleware('api')
                ->prefix('api/pos')
                ->group(base_path('routes/api_pos.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust reverse proxy (nginx/load balancer) so $request->ip() returns real client IP
        $trustedProxies = env('TRUSTED_PROXIES', '');
        if (!empty($trustedProxies)) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        // CSRF Exceptions pour les webhooks
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/webhooks/*',
            'payment/monetbil/notify',
            'api/pos/register', // POS terminal registration
        ]);

        // Enregistrer les middlewares personnalisés
        $middleware->alias([
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            
            // PHASE 3: Unified Authentication & Authorization
            'ensure' => \App\Http\Middleware\EnsureAuthenticated::class,
            
            // Middlewares de sécurité critiques (réactivés pour production)
            'permission' => \App\Http\Middleware\CheckPermission::class,
            '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
            
            // Middlewares actifs (business logic)
            'creator.active' => \App\Http\Middleware\EnsureCreatorActive::class,
            'capability' => \App\Http\Middleware\EnsureCapability::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'pos.device' => \App\Http\Middleware\PosDeviceAuth::class,
            'pos.auth' => \App\Http\Middleware\PosDeviceAuth::class,
            
            // Legacy webhook guards (deprecated routes)
            'legacy.webhook.guard' => \App\Http\Middleware\LegacyWebhookGuard::class,
            'legacy.webhook.deprecation' => \App\Http\Middleware\LegacyWebhookDeprecation::class,
            
            // Aliases needed by framework (Laravel auto-appends these in some cases)
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'creator' => \App\Http\Middleware\EnsureAuthenticated::class, // Legacy alias, use 'ensure:createur' instead
            'admin' => \App\Http\Middleware\EnsureAuthenticated::class . ':admin,super_admin',
            'role' => \App\Http\Middleware\EnsureAuthenticated::class,
            'role.creator' => \App\Http\Middleware\EnsureAuthenticated::class . ':createur',
            'terms' => \App\Http\Middleware\EnsureTermsAccepted::class,
        ]);


        // Headers de sécurité HTTP (global)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Assign unique Request ID (global correlation)
        $middleware->append(\App\Http\Middleware\AssignRequestId::class);
        
        // Définir la locale (global)

        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Fusion automatique panier session → DB à la connexion
        $middleware->append(\App\Http\Middleware\MergeCartOnLogin::class);

        // Group 'web' configuration
        $middleware->web(append: [
            \App\Http\Middleware\EnsureTermsAccepted::class,
            \App\Http\Middleware\DetectUserCurrency::class,
        ]);

        // Enregistrement des métriques de performance (disabled for local dev)
        // $middleware->append(\App\Http\Middleware\RecordPerformanceMetrics::class);

        // Rate limiting global
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/pos/*') || $request->is('pos/*') || ($request->expectsJson() && !$request->is('pos-terminal/*'))) {
                return response()->json([
                    'success' => false,
                    'error'   => 'UNAUTHENTICATED',
                    'message' => 'Token opérateur Sanctum manquant ou invalide.',
                ], 401);
            }
        });

        $exceptions->render(function (PosException $e, Request $request) {
            if ($request->is('api/pos/*') || $request->is('pos/*')) {
                return PosApiResponse::error(
                    $e->getErrorCode(),
                    $e->getMessage(),
                    null,
                    $e->getHttpStatus()
                );
            }

            return null;
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Trop de requêtes, patientez un instant.',
                ], 429, $e->getHeaders());
            }
            return response()->view('errors.429', [], 429);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }

            // Redirect back with error instead of logging out — a permission error
            // on one action should not terminate the entire session.
            return redirect()->back()
                ->with('error', 'Vous n\'avez pas les permissions nécessaires pour effectuer cette action.');
        });
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        // Planifier la vérification des alertes de stock (quotidien à 8h)
        $schedule->command('erp:check-stock-alerts')
            ->dailyAt('08:00')
            ->description('Vérifie les stocks faibles et envoie des alertes');
        
        // R4 : Nettoyer les paiements Mobile Money en attente (toutes les 30 minutes)
        $schedule->job(\App\Jobs\CleanupPendingMobileMoneyPayments::class)
            ->everyThirtyMinutes()
            ->description('Nettoie les paiements Mobile Money en attente depuis plus de 30 minutes');
        
        // Expirer les transactions Monetbil en attente (toutes les 30 minutes)
        $schedule->command('monetbil:expire-pending --minutes=30')
            ->everyThirtyMinutes()
            ->description('Expire les transactions Monetbil en attente depuis plus de 30 minutes');
        
        // P3 : Nettoyer les commandes abandonnées (quotidien à 2h du matin)
        $schedule->job(\App\Jobs\CleanupAbandonedOrders::class)
            ->dailyAt('02:00')
            ->description('Nettoie les commandes abandonnées (cash > 7 jours, card > 24h, mobile_money > 48h)');
        
        // Payments Hub : Purge des événements webhook/callback (quotidien à 2h du matin)
        $schedule->command('payments:prune-events')
            ->dailyAt('02:00')
            ->description('Purge les événements webhook/callback anciens (politique de rétention)');
        
        // Payments Hub : Purge des logs d'audit (mensuel)
        $schedule->command('payments:prune-audit-logs')
            ->monthly()
            ->description('Purge les logs d\'audit paiements anciens (politique de rétention)');
        
        // Payments Hub : Requeue automatique des événements stuck (Patch 4.3)
        if (config('payments.webhooks.stuck_requeue_enabled', true)) {
            $minutes = config('payments.webhooks.stuck_requeue_minutes', 10);
            $schedule->command("payments:requeue-stuck-webhooks --minutes={$minutes}")
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->onOneServer()
                ->description('Requeue automatique des événements webhook/callback stuck');
        }

        // Payments Hub : Prune des événements webhooks (Patch 4.4)
        $schedule->command('payments:prune-webhook-events')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->description('Prune des événements webhook/callback anciens (politique de rétention)');
        
        // PHASE 9: Vérification des abonnements expirés (quotidien à 3h du matin)
        $schedule->command('creator:check-expired-subscriptions')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->onOneServer()
            ->description('Downgrade automatique des abonnements expirés vers FREE');

        // Rejouer les webhooks en échec (toutes les 10 minutes)
        $schedule->command('webhook:retry-failures --limit=20')
            ->everyTenMinutes()
            ->withoutOverlapping(5)
            ->onOneServer()
            ->description('Rejoue les webhooks Stripe/Monetbil en échec');

        // CRM: Sync segments automatiques (quotidien à 2h)
        $schedule->call(fn() => app(\App\Services\Crm\SegmentationService::class)->syncAllCustomers())
            ->name('crm-sync-segments')
            ->dailyAt('02:00')
            ->onOneServer()
            ->description('Synchronise les segments clients selon les règles automatiques');

        // CRM: Expiration des points de fidélité (mensuel)
        $schedule->call(fn() => app(\App\Services\Crm\LoyaltyService::class)->expirePoints())
            ->name('crm-expire-points')
            ->monthly()
            ->onOneServer()
            ->description('Expire les points de fidélité > 365 jours');

        // Expirer ventes offline > 24h
        $schedule->call(fn() => app(\App\Services\Pos\PosOfflineService::class)->expireOldSales())
            ->hourly()
            ->description('Expire ventes offline > 24h');

        // AI Module Scheduled Jobs
        $schedule->job(\App\Jobs\AI\AnalyzeCreatorSales::class)
            ->dailyAt('06:00')
            ->description('IA: Analyse ventes créateurs');

        $schedule->job(\App\Jobs\AI\DetectStockAnomalies::class)
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->description('IA: Détection anomalies stock');

        $schedule->job(\App\Jobs\AI\GenerateAdminSummary::class)
            ->dailyAt('07:00')
            ->description('IA: Résumé quotidien admin');
    })
    ->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
        \App\Providers\RateLimitServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            // POS Routes (Audit-Ready Architecture)
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/pos.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CSRF Exceptions pour les webhooks
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/webhooks/*',
            'payment/card/webhook',
            'payment/monetbil/notify',
        ]);

        // Enregistrer les middlewares personnalisés
        $middleware->alias([
            // Middlewares de sécurité critiques (réactivés pour production)
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
            
            // Middlewares actifs
            'creator' => \App\Http\Middleware\CreatorMiddleware::class,
            'role.creator' => \App\Http\Middleware\EnsureCreatorRole::class,
            'creator.active' => \App\Http\Middleware\EnsureCreatorActive::class,
            'capability' => \App\Http\Middleware\EnsureCapability::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
            'legacy.webhook.deprecation' => \App\Http\Middleware\LegacyWebhookDeprecationHeaders::class,
            'legacy.webhook.guard' => \App\Http\Middleware\LegacyWebhookGuard::class,
        ]);

        // Headers de sécurité HTTP (global)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Définir la locale (global)
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Fusion automatique panier session → DB à la connexion
        $middleware->append(\App\Http\Middleware\MergeCartOnLogin::class);

        // Enregistrement des métriques de performance (debug uniquement)
        $middleware->append(\App\Http\Middleware\RecordPerformanceMetrics::class);

        // Rate limiting global
        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
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
    })
    ->create();

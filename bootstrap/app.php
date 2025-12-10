<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CSRF Exceptions pour les webhooks
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'payment/card/webhook',
        ]);

        // Enregistrer les middlewares personnalisés
        $middleware->alias([
            // Middlewares désactivés temporairement pour débugger l'auth
            // 'role' => \App\Http\Middleware\CheckRole::class,
            // 'permission' => \App\Http\Middleware\CheckPermission::class,
            // '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
            
            // Middlewares actifs
            'creator' => \App\Http\Middleware\CreatorMiddleware::class,
            'role.creator' => \App\Http\Middleware\EnsureCreatorRole::class,
            'creator.active' => \App\Http\Middleware\EnsureCreatorActive::class,
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'staff' => \App\Http\Middleware\StaffMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // Headers de sécurité HTTP (global)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        
        // Définir la locale (global)
        $middleware->append(\App\Http\Middleware\SetLocale::class);

        // Fusion automatique panier session → DB à la connexion
        $middleware->append(\App\Http\Middleware\MergeCartOnLogin::class);

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
        
        // P3 : Nettoyer les commandes abandonnées (quotidien à 2h du matin)
        $schedule->job(\App\Jobs\CleanupAbandonedOrders::class)
            ->dailyAt('02:00')
            ->description('Nettoie les commandes abandonnées (cash > 7 jours, card > 24h, mobile_money > 48h)');
    })
    ->create();

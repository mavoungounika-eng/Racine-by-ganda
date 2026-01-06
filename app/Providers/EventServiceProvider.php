<?php

namespace App\Providers;

use App\Events\CheckoutStarted;
use App\Events\OrderPlaced;
use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Events\ProductAddedToCart;
use App\Listeners\LogFunnelEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Event Service Provider pour le monitoring du funnel d'achat
 * 
 * Phase 3 : Enregistrement des events/listeners pour le tracking
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        ProductAddedToCart::class => [
            [LogFunnelEvent::class, 'handleProductAddedToCart'],
        ],
        CheckoutStarted::class => [
            [LogFunnelEvent::class, 'handleCheckoutStarted'],
        ],
        OrderPlaced::class => [
            [LogFunnelEvent::class, 'handleOrderPlaced'],
        ],
        PaymentCompleted::class => [
            [LogFunnelEvent::class, 'handlePaymentCompleted'],
        ],
        PaymentFailed::class => [
            [LogFunnelEvent::class, 'handlePaymentFailed'],
        ],
        // ==========================================
        // POS Events (Audit-Ready Architecture)
        // ==========================================
        \App\Events\PosSessionClosed::class => [
            \App\Listeners\PosSessionClosedListener::class,
        ],
        \App\Events\PosCardPaymentConfirmed::class => [
            \App\Listeners\PosCardPaymentConfirmedListener::class,
        ],
        \App\Events\PosMobilePaymentConfirmed::class => [
            \App\Listeners\PosMobilePaymentConfirmedListener::class,
        ],
        // ✅ Phase 2 : Limiter les sessions actives
        \Illuminate\Auth\Events\Login::class => [
            \App\Listeners\LogSuccessfulLogin::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}


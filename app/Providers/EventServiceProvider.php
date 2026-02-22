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
        // Accounting
        \Modules\Accounting\Events\PaymentRecorded::class => [
            \Modules\Accounting\Listeners\PaymentRecordedListener::class,
        ],
        \Modules\Accounting\Events\PurchaseReceived::class => [
            \Modules\Accounting\Listeners\PurchaseReceivedListener::class,
        ],
        \Modules\ERPProduction\Events\ProductionStarted::class => [
            \Modules\Accounting\Listeners\ProductionStartedListener::class,
        ],
        \Modules\ERPProduction\Events\ProductionFinished::class => [
            \Modules\Accounting\Listeners\ProductionFinishedListener::class,
        ],
        \Modules\ERPProduction\Events\ProductionScrapped::class => [
            \Modules\Accounting\Listeners\ProductionScrappedListener::class,
        ],
        // ==========================================
        // POS Events (Audit-Ready Architecture)
        // ==========================================
        \App\Events\PosSessionClosed::class => [
            \App\Listeners\PosSessionClosedListener::class,
        ],
        \App\Events\CashDiscrepancyDetected::class => [
            \App\Listeners\SendCashDiscrepancyAlert::class,
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
        // Register model observers
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        
        // Task 3: Global Audit Trail Observers
        \App\Models\User::observe(\App\Observers\AuditObserver::class);
        if (class_exists(\App\Models\Order::class)) {
            \App\Models\Order::observe(\App\Observers\AuditObserver::class);
        }
        if (class_exists(\App\Models\Payment::class)) {
            \App\Models\Payment::observe(\App\Observers\AuditObserver::class);
        }
        if (class_exists(\App\Models\Product::class)) {
            \App\Models\Product::observe(\App\Observers\AuditObserver::class);
        }
        if (class_exists(\App\Models\Role::class)) {
            \App\Models\Role::observe(\App\Observers\AuditObserver::class);
        }
        if (class_exists(\App\Models\CreatorProfile::class)) {
            \App\Models\CreatorProfile::observe(\App\Observers\AuditObserver::class);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}

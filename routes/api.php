<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Monitoring\HealthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Webhooks Payments Hub (pattern v1.1 : persist event → dispatch job → 200)
// Exclus du middleware CSRF et auth car appelés directement par les providers
// Throttle: utilise le rate limiter 'webhooks' (60 requêtes par minute par IP)
Route::middleware(['api', 'throttle:webhooks'])->group(function () {
    Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\WebhookController::class, 'stripe'])->name('api.webhooks.stripe');
    Route::post('/webhooks/monetbil', [\App\Http\Controllers\Api\WebhookController::class, 'monetbil'])->name('api.webhooks.monetbil');
});

// Webhooks Stripe Billing (abonnements créateurs)
// Exclus du middleware CSRF et auth car appelés directement par Stripe
// Throttle: utilise le rate limiter 'webhooks' (60 requêtes par minute par IP)
Route::middleware(['api'])->group(function () {
    Route::post('/webhooks/stripe/billing', [\App\Http\Controllers\Webhooks\StripeBillingWebhookController::class, '__invoke'])->name('api.webhooks.stripe.billing');
    
    // ✅ C6: Stripe Subscriptions Créateur (nouveau - sécurisé)
    Route::post('/webhooks/stripe/creator-subscriptions', [\App\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])
        ->name('api.webhooks.stripe.creator-subscriptions');
});

// ==========================================
// Testing Routes (Idempotency & Rate Limiting)
// Only available in testing environment to avoid conflicts
// ==========================================
if (app()->environment('testing')) {
    Route::get('/test-idempotency', function () {
        return response()->json(['success' => true]);
    })->name('api.test.idempotency.get');

    Route::post('/test-idempotency', function () {
        return response()->json(['success' => true, 'timestamp' => now()->toDateTimeString()]);
    })->middleware(\App\Http\Middleware\CheckIdempotency::class)
     ->name('api.test.idempotency.post');

    // ✅ Rate Limiting Test Routes (match actual routes but return simple responses)
    // POS Sessions (throttle:30,1)
    Route::post('/api-test/pos-sessions-open', function () {
        return response()->json(['success' => true, 'session_id' => 'test-session']);
    })->middleware('throttle:30,1')->name('api.test.pos.sessions.open');

    // 2FA Routes (throttle:10,1 for verify, throttle:5,1 for confirm)
    Route::post('/api-test/2fa-verify', function () {
        return response()->json(['success' => true, 'code_sent' => true]);
    })->middleware('throttle:10,1')->name('api.test.2fa.verify');

    Route::post('/api-test/2fa-confirm', function () {
        return response()->json(['success' => true, 'authenticated' => true]);
    })->middleware('throttle:5,1')->name('api.test.2fa.confirm');

    // Checkout (throttle:10,1)
    Route::post('/api-test/checkout-test', function () {
        return response()->json(['success' => true, 'order_id' => 'test-order']);
    })->middleware('throttle:10,1')->name('api.test.checkout');

    // Login (throttle:5,1)
    Route::post('/api-test/login-test', function () {
        return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
    })->middleware('throttle:5,1')->name('api.test.login');

    // Register (throttle:3,1)
    Route::post('/api-test/register-test', function () {
        return response()->json(['success' => false, 'errors' => ['email' => 'Already exists']], 422);
    })->middleware('throttle:3,1')->name('api.test.register');

    // Webhooks (throttle:60,1)
    Route::post('/api-test/webhook-test-stripe', function () {
        return response()->json(['received' => true]);
    })->middleware('throttle:60,1')->name('api.test.webhook');
}

// ==========================================
// Admin API Routes — Reports POS
// ==========================================
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // POS Reports
    Route::prefix('pos/reports')->group(function () {
        Route::get('/daily', [\App\Http\Controllers\Api\Admin\PosReportsController::class, 'daily'])->name('api.admin.pos.reports.daily');
        Route::get('/period', [\App\Http\Controllers\Api\Admin\PosReportsController::class, 'period'])->name('api.admin.pos.reports.period');
        Route::get('/discrepancies', [\App\Http\Controllers\Api\Admin\PosReportsController::class, 'discrepancies'])->name('api.admin.pos.reports.discrepancies');
        Route::get('/export', [\App\Http\Controllers\Api\Admin\PosReportsController::class, 'export'])->name('api.admin.pos.reports.export');
        Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\PosReportsController::class, 'dashboard'])->name('api.admin.pos.reports.dashboard');
    });
});
// ==========================================
// Webhook Monitoring & Observability Routes
// Available to authenticated admin users for monitoring
// ==========================================
Route::middleware(['auth', 'role:admin'])->prefix('webhooks/monitoring')->group(function () {
    Route::get('/prometheus', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'prometheusMetrics'])
        ->name('api.webhooks.monitoring.prometheus');
    Route::get('/dashboard', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'dashboard'])
        ->name('api.webhooks.monitoring.dashboard');
    Route::get('/health', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'systemHealth'])
        ->name('api.webhooks.monitoring.health');
    Route::get('/health/{provider}', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'providerHealth'])
        ->name('api.webhooks.monitoring.provider.health');
    Route::get('/kpis', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'kpis'])
        ->name('api.webhooks.monitoring.kpis');
    Route::get('/sla', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'slaMetrics'])
        ->name('api.webhooks.monitoring.sla');
    Route::get('/alerts', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'alertStatus'])
        ->name('api.webhooks.monitoring.alerts');
    Route::get('/errors', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'errorAnalysis'])
        ->name('api.webhooks.monitoring.errors');
    Route::get('/trends', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'performanceTrends'])
        ->name('api.webhooks.monitoring.trends');
    Route::get('/report', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'generateReport'])
        ->name('api.webhooks.monitoring.report');
    Route::get('/metrics/json', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'metricsJson'])
        ->name('api.webhooks.monitoring.metrics.json');
});

// ==========================================
// Webhook Status Page (Public)
// For third-party monitoring service integration
// ==========================================
Route::get('/webhooks/status', [\App\Http\Controllers\Webhooks\WebhookMonitoringController::class, 'statusPage'])
    ->name('webhooks.status.page');

// ==========================================
// Health & Liveness Monitoring
// ==========================================
Route::get('/health', [HealthController::class, 'index'])->name('api.health');
Route::get('/liveness', [HealthController::class, 'liveness'])->name('api.liveness');

// ==========================================
// Multi-Account & Governance
// ==========================================
Route::middleware(['auth:sanctum'])->group(function () {
    // Switcher
    Route::get('/auth/accounts', [\App\Http\Controllers\Auth\AccountSwitcherController::class, 'list'])->name('api.auth.accounts.list');
    Route::post('/auth/accounts/{creatorId}/switch', [\App\Http\Controllers\Auth\AccountSwitcherController::class, 'switch'])->name('api.auth.accounts.switch');

    // Team Management (Creator only, requires active account)
    Route::middleware(['role:createur', \App\Http\Middleware\EnsureActiveAccount::class])->prefix('creator/team')->group(function () {
        Route::get('/', [\App\Http\Controllers\Creator\TeamManagementController::class, 'index'])->name('api.creator.team.index');
        Route::post('/invite', [\App\Http\Controllers\Creator\TeamManagementController::class, 'invite'])->name('api.creator.team.invite');
        Route::post('/members/{userId}/role', [\App\Http\Controllers\Creator\TeamManagementController::class, 'updateRole'])->name('api.creator.team.role.update');
        Route::delete('/members/{userId}', [\App\Http\Controllers\Creator\TeamManagementController::class, 'removeMember'])->name('api.creator.team.member.remove');
    });
});
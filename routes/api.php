<?php

use Illuminate\Support\Facades\Route;

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
Route::middleware(['api', 'throttle:webhooks'])->group(function () {
    Route::post('/webhooks/stripe/billing', [\App\Http\Controllers\Webhooks\StripeBillingWebhookController::class])->name('api.webhooks.stripe.billing');
    
    // ✅ C6: Stripe Subscriptions Créateur (nouveau - sécurisé)
    Route::post('/webhooks/stripe/creator-subscriptions', [\App\Http\Controllers\Webhooks\StripeWebhookController::class, 'handle'])
        ->name('api.webhooks.stripe.creator-subscriptions');
});

// ==========================================
// Testing Routes (Idempotency)
// ==========================================
if (app()->environment('testing')) {
    Route::get('/test-idempotency', function () {
        return response()->json(['success' => true]);
    })->name('api.test.idempotency.get');

    Route::post('/test-idempotency', function () {
        return response()->json(['success' => true, 'timestamp' => now()->toDateTimeString()]);
    })->middleware(\App\Http\Middleware\CheckIdempotency::class)
     ->name('api.test.idempotency.post');
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

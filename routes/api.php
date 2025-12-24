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
});




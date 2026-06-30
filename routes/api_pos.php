<?php

use App\Http\Controllers\Pos\PosOfflineStatusController;
use App\Http\Controllers\Pos\PosOfflineController;
use App\Http\Controllers\Pos\PosOfflineSyncController;
use App\Http\Controllers\Pos\PosPaymentController;
use App\Http\Controllers\Pos\PosProductController;
use App\Http\Controllers\Pos\PosSaleController;
use App\Http\Controllers\Pos\PosSessionController;
use App\Http\Controllers\Pos\PosAuthController;
use App\Http\Controllers\Pos\PosAnalyticsController;
use App\Http\Controllers\Pos\PosCouponController;
use Illuminate\Support\Facades\Route;

// POS Terminal Registration (no auth required)
Route::post('/register', [PosAuthController::class, 'registerTerminal']);

// Connectivity ping (no auth required — used by POS to detect online/offline).
// Must remain public: the POS pings this BEFORE registration/login.
Route::get('/offline/status', [PosOfflineController::class, 'status']);
Route::get('/offline-status', [PosOfflineStatusController::class, 'index']);

// POS Operator Auth (no device JWT required).
// Throttling via named limiter `pos_operator_login` (cf. RateLimitServiceProvider)
// pour retourner une réponse JSON structurée et un header Retry-After exploitable
// côté front au lieu de la page HTML par défaut de Laravel.
Route::prefix('auth')->middleware(['throttle:pos_operator_login'])->group(function () {
    Route::post('/operator/login', [PosAuthController::class, 'login']);
    // Renouvellement JWT device (expiré ou même après migration DB)
    Route::post('/refresh', [PosAuthController::class, 'refreshDeviceToken']);
});

Route::middleware(['pos.auth', 'throttle:pos_device'])->group(function () {
    // Device token verification. Protected by pos.auth so invalid/expired
    // tokens return 401/403 before reaching the controller.
    Route::get('/device/verify', [PosAuthController::class, 'verifyDevice']);

    // Operator endpoints protected by device JWT + X-Operator-Token
    Route::post('/auth/operator/logout', [PosAuthController::class, 'logout']);
    Route::get('/auth/operator/me', [PosAuthController::class, 'me']);

    // Offline queue management (status moved outside auth above)
    Route::prefix('offline')->group(function () {
        Route::get('/queue', [PosOfflineController::class, 'queue']);
        Route::post('/queue/flush', [PosOfflineController::class, 'flush']);
        Route::post('/queue/{item}/submit', [PosOfflineController::class, 'submit']);
        Route::delete('/queue', [PosOfflineController::class, 'clear']);

        // Offline Sync Endpoints
        Route::post('/sync', [PosOfflineSyncController::class, 'sync']);
        Route::post('/conflict/resolve', [PosOfflineSyncController::class, 'resolveConflict']);
    });

    // Sessions
    Route::prefix('sessions')->group(function () {
        Route::get('/last-closing-cash', [PosSessionController::class, 'lastClosingCash']);
        Route::post('/open', [PosSessionController::class, 'open']);
        Route::get('/current', [PosSessionController::class, 'current']);
        Route::get('/{session}/prepare-close', [PosSessionController::class, 'prepareClose']);
        Route::post('/{session}/close', [PosSessionController::class, 'close']);
        Route::get('/{session}/z-report', [PosSessionController::class, 'zReport']);
        Route::post('/{session}/adjustments', [PosSessionController::class, 'createAdjustment']);
        Route::get('/{session}/sales', [PosSaleController::class, 'forSession']);
    });

    // Sales
    Route::prefix('sales')->group(function () {
        Route::post('/', [PosSaleController::class, 'store']);
        Route::get('/{sale}', [PosSaleController::class, 'show']);
        Route::post('/{sale}/cancel', [PosSaleController::class, 'cancel']);
    });

    // Payments
    Route::prefix('payments')->group(function () {
        Route::get('/{payment}/status', [PosPaymentController::class, 'status']);
        Route::post('/{payment}/confirm-card', [PosPaymentController::class, 'confirmCard']);
    });

    // Product catalog
    Route::prefix('products')->group(function () {
        Route::get('/', [PosProductController::class, 'index']);
        Route::get('/search', [PosProductController::class, 'search']);
        Route::get('/categories', [PosProductController::class, 'categories']);
        Route::get('/{product}', [PosProductController::class, 'show']);
    });

    // Coupons & Promos
    Route::prefix('coupons')->group(function () {
        Route::get('/validate', [PosCouponController::class, 'validateCoupon']);
    });

    // Analytics & Reports
    Route::prefix('analytics')->group(function () {
        Route::get('/daily', [PosAnalyticsController::class, 'daily']);
        Route::get('/sessions', [PosAnalyticsController::class, 'sessions']);
        Route::get('/top-products', [PosAnalyticsController::class, 'topProducts']);
        Route::get('/period', [PosAnalyticsController::class, 'periodReport']);
        Route::get('/low-stock', [PosAnalyticsController::class, 'lowStock']);
        Route::get('/export', [PosAnalyticsController::class, 'export']);
    });
});

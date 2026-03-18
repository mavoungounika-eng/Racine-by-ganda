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
use Illuminate\Support\Facades\Route;

// POS Operator Auth (no device JWT required)
Route::prefix('auth')->middleware(['throttle:5,1'])->group(function () {
    Route::post('/operator/login', [PosAuthController::class, 'login']);
});

Route::middleware(['pos.auth', 'throttle:pos_device'])->group(function () {
    // Operator endpoints protected by device JWT + X-Operator-Token
    Route::post('/auth/operator/logout', [PosAuthController::class, 'logout']);
    Route::get('/auth/operator/me', [PosAuthController::class, 'me']);

    // Offline status
    Route::get('/offline-status', [PosOfflineStatusController::class, 'index']);

    // Offline queue management
    Route::prefix('offline')->group(function () {
        Route::get('/status', [PosOfflineController::class, 'status']);
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

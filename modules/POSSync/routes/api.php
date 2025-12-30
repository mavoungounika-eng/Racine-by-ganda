<?php

use Illuminate\Support\Facades\Route;
use Modules\POSSync\Http\Controllers\SyncGatewayController;

/*
|--------------------------------------------------------------------------
| API Routes - POS Sync Module
|--------------------------------------------------------------------------
*/

Route::prefix('api/pos')->group(function () {
    
    // Enregistrement d'un nouveau device POS
    Route::post('/register', [SyncGatewayController::class, 'registerDevice']);
    
    // Routes protégées par JWT
    Route::middleware('auth:pos')->group(function () {
        
        // Synchronisation des événements (endpoint principal)
        Route::post('/sync', [SyncGatewayController::class, 'syncEvents']);
        
        // Renouvellement du token JWT
        Route::post('/auth/refresh', [SyncGatewayController::class, 'refreshToken']);
        
        // Statut du device
        Route::get('/status', [SyncGatewayController::class, 'getDeviceStatus']);
        
    });
});

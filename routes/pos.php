<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Pos\PosSessionController;
use App\Http\Controllers\Pos\PosSaleController;
use App\Http\Controllers\Pos\PosPaymentController;

/*
|--------------------------------------------------------------------------
| POS Routes - Point of Sale Audit-Ready
|--------------------------------------------------------------------------
|
| Routes pour le système POS audit-ready avec sessions de caisse obligatoires,
| paiements cash vérifiés à la clôture, et intégration Intent-Based Finance.
|
*/

Route::middleware(['auth', 'verified'])->prefix('pos')->name('pos.')->group(function () {

    // ==========================================
    // Sessions de caisse
    // ==========================================
    Route::prefix('sessions')->name('sessions.')->group(function () {
        // Ouvrir une session
        Route::post('/open', [PosSessionController::class, 'open'])->name('open');
        
        // Obtenir session courante
        Route::get('/current', [PosSessionController::class, 'current'])->name('current');
        
        // Actions sur une session spécifique
        Route::prefix('{session}')->group(function () {
            // Préparer clôture (calcul expected_cash)
            Route::get('/prepare-close', [PosSessionController::class, 'prepareClose'])->name('prepare-close');
            
            // Clôturer la session
            Route::post('/close', [PosSessionController::class, 'close'])->name('close');
            
            // Z-Report
            Route::get('/z-report', [PosSessionController::class, 'zReport'])->name('z-report');
            
            // Ajustements
            Route::post('/adjustments', [PosSessionController::class, 'createAdjustment'])->name('adjustments.store');
            
            // Ventes de la session
            Route::get('/sales', [PosSaleController::class, 'forSession'])->name('sales');
        });
    });

    // ==========================================
    // Ventes POS
    // ==========================================
    Route::prefix('sales')->name('sales.')->group(function () {
        // Créer une vente
        Route::post('/', [PosSaleController::class, 'store'])->name('store');
        
        // Détails d'une vente
        Route::get('/{sale}', [PosSaleController::class, 'show'])->name('show');
        
        // Annuler une vente
        Route::post('/{sale}/cancel', [PosSaleController::class, 'cancel'])->name('cancel');
    });

    // ==========================================
    // Paiements POS
    // ==========================================
    Route::prefix('payments')->name('payments.')->group(function () {
        // Statut d'un paiement
        Route::get('/{payment}/status', [PosPaymentController::class, 'status'])->name('status');
        
        // Confirmer paiement carte (TPE)
        Route::post('/{payment}/confirm-card', [PosPaymentController::class, 'confirmCard'])->name('confirm-card');
    });
});

// ==========================================
// Webhooks (sans auth middleware)
// ==========================================
Route::prefix('webhooks/pos')->name('webhooks.pos.')->group(function () {
    // Webhook Monetbil pour paiements mobile
    Route::post('/mobile', [PosPaymentController::class, 'webhookMobile'])->name('mobile');
});

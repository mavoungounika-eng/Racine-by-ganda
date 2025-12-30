<?php

use Illuminate\Support\Facades\Route;
use Modules\ERP\Http\Controllers\ErpDashboardController;
use Modules\ERP\Http\Controllers\ErpStockController;
use Modules\ERP\Http\Controllers\ErpSupplierController;
use Modules\ERP\Http\Controllers\ErpRawMaterialController;
use Modules\ERP\Http\Controllers\ErpReportController;

/*
|--------------------------------------------------------------------------
| Module ERP - Routes Web
|--------------------------------------------------------------------------
|
| Routes pour le module ERP (Gestion des stocks, fournisseurs, etc.)
| Accessible uniquement aux rôles : staff, admin, super_admin
|
*/

$rateLimitMax = config('erp.rate_limit.max_attempts', 60);
$rateLimitDecay = config('erp.rate_limit.decay_minutes', 1);

Route::prefix('erp')->name('erp.')->middleware(['auth', 'can:access-erp', '2fa', "throttle:{$rateLimitMax},{$rateLimitDecay}"])->group(function () {
    
    // Dashboard ERP
    Route::get('/', [ErpDashboardController::class, 'index'])->name('dashboard');
    
    // Gestion des Stocks
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/', [ErpStockController::class, 'index'])->name('index');
        Route::get('/mouvements', [ErpStockController::class, 'movements'])->name('movements');
        Route::get('/mouvements/export', [ErpStockController::class, 'exportMovements'])->name('movements.export');
        Route::get('/{product}/adjust', [ErpStockController::class, 'adjust'])->name('adjust');
        Route::post('/{product}/adjust', [ErpStockController::class, 'storeAdjustment'])->name('store-adjustment');
    });
    
    // Gestion des Fournisseurs
    Route::resource('fournisseurs', ErpSupplierController::class)->names([
        'index' => 'suppliers.index',
        'create' => 'suppliers.create',
        'store' => 'suppliers.store',
        'show' => 'suppliers.show',
        'edit' => 'suppliers.edit',
        'update' => 'suppliers.update',
        'destroy' => 'suppliers.destroy',
    ]);
    
    // Gestion des Matières Premières
    Route::resource('matieres', ErpRawMaterialController::class)->names([
        'index' => 'materials.index',
        'create' => 'materials.create',
        'store' => 'materials.store',
        'show' => 'materials.show',
        'edit' => 'materials.edit',
        'update' => 'materials.update',
        'destroy' => 'materials.destroy',
    ]);

    // Gestion des Achats
    Route::resource('achats', \Modules\ERP\Http\Controllers\ErpPurchaseController::class)->names([
        'index' => 'purchases.index',
        'create' => 'purchases.create',
        'store' => 'purchases.store',
        'show' => 'purchases.show',
        'edit' => 'purchases.edit',
        'update' => 'purchases.update',
        'destroy' => 'purchases.destroy',
    ]);
    
    Route::post('achats/{purchase}/status', [\Modules\ERP\Http\Controllers\ErpPurchaseController::class, 'updateStatus'])->name('purchases.update-status');
    
    // Rapports
    Route::prefix('rapports')->name('reports.')->group(function () {
        Route::get('valorisation-stock', [ErpReportController::class, 'stockValuationReport'])->name('stock-valuation');
        Route::get('achats', [ErpReportController::class, 'purchasesReport'])->name('purchases');
        Route::get('mouvements-stock', [ErpReportController::class, 'stockMovementsReport'])->name('stock-movements');
        Route::get('suggestions-reapprovisionnement', [ErpReportController::class, 'replenishmentSuggestions'])->name('replenishment-suggestions');
    });
    
});


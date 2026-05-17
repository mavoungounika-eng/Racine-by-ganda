<?php

use Illuminate\Support\Facades\Route;
use Modules\ERP\Http\Controllers\ErpDashboardController;
use Modules\ERP\Http\Controllers\ErpStockController;
use Modules\ERP\Http\Controllers\ErpSupplierController;
use Modules\ERP\Http\Controllers\ErpRawMaterialController;
use Modules\ERP\Http\Controllers\ErpReportController;
use Modules\ERP\Http\Controllers\ErpPurchaseController;
use Modules\ERP\Http\Controllers\ErpReceptionController;

$rateLimitMax   = config('erp.rate_limit.max_attempts', 60);
$rateLimitDecay = config('erp.rate_limit.decay_minutes', 1);

Route::prefix('erp')->name('erp.')->middleware(['auth', 'ensure:staff,admin,super_admin', '2fa', "throttle:{$rateLimitMax},{$rateLimitDecay}"])->group(function () {

    // Dashboard ERP
    Route::get('/', [ErpDashboardController::class, 'index'])->name('dashboard');

    // ─── Stocks ──────────────────────────────────────────────────────────────
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/data',      [ErpStockController::class, 'dataStocks'])->name('data');
        Route::get('/export/csv',[ErpStockController::class, 'exportCsv'])->name('export.csv');
        Route::get('/',          [ErpStockController::class, 'index'])->name('index');
        Route::get('/mouvements',        [ErpStockController::class, 'movements'])->name('movements');
        Route::get('/mouvements/export', [ErpStockController::class, 'exportMovements'])->name('movements.export');
        Route::get('/{product}/adjust',  [ErpStockController::class, 'adjust'])->name('adjust');
        Route::post('/{product}/adjust', [ErpStockController::class, 'storeAdjustment'])->name('store-adjustment');
    });

    // ─── Fournisseurs ─────────────────────────────────────────────────────────
    Route::get('fournisseurs/data',        [ErpSupplierController::class, 'dataSuppliers'])->name('suppliers.data');
    Route::post('fournisseurs/bulk-delete', [ErpSupplierController::class, 'bulkDelete'])->name('suppliers.bulk-delete');
    Route::get('fournisseurs/export/csv',  [ErpSupplierController::class, 'exportCsv'])->name('suppliers.export.csv');
    Route::resource('fournisseurs', ErpSupplierController::class)->names([
        'index'   => 'suppliers.index',
        'create'  => 'suppliers.create',
        'store'   => 'suppliers.store',
        'show'    => 'suppliers.show',
        'edit'    => 'suppliers.edit',
        'update'  => 'suppliers.update',
        'destroy' => 'suppliers.destroy',
    ]);

    // ─── Matières premières ───────────────────────────────────────────────────
    Route::get('matieres/data',        [ErpRawMaterialController::class, 'dataMaterials'])->name('materials.data');
    Route::post('matieres/bulk-delete', [ErpRawMaterialController::class, 'bulkDelete'])->name('materials.bulk-delete');
    Route::get('matieres/export/csv',  [ErpRawMaterialController::class, 'exportCsv'])->name('materials.export.csv');
    Route::resource('matieres', ErpRawMaterialController::class)->names([
        'index'   => 'materials.index',
        'create'  => 'materials.create',
        'store'   => 'materials.store',
        'show'    => 'materials.show',
        'edit'    => 'materials.edit',
        'update'  => 'materials.update',
        'destroy' => 'materials.destroy',
    ]);

    // ─── Achats ───────────────────────────────────────────────────────────────
    Route::get('achats/data',        [ErpPurchaseController::class, 'dataPurchases'])->name('purchases.data');
    Route::post('achats/bulk-delete', [ErpPurchaseController::class, 'bulkDelete'])->name('purchases.bulk-delete');
    Route::get('achats/export/csv',  [ErpPurchaseController::class, 'exportCsv'])->name('purchases.export.csv');
    Route::resource('achats', ErpPurchaseController::class)->names([
        'index'   => 'purchases.index',
        'create'  => 'purchases.create',
        'store'   => 'purchases.store',
        'show'    => 'purchases.show',
        'edit'    => 'purchases.edit',
        'update'  => 'purchases.update',
        'destroy' => 'purchases.destroy',
    ]);
    Route::post('achats/{purchase}/status',               [ErpPurchaseController::class, 'updateStatus'])->name('purchases.update-status');
    Route::get('achats/{purchase}/pdf',                    [ErpPurchaseController::class, 'pdf'])->name('purchases.pdf');
    Route::get('achats/{purchase}/reception/create',       [ErpReceptionController::class, 'create'])->name('purchases.reception.create');
    Route::post('achats/{purchase}/reception',             [ErpReceptionController::class, 'store'])->name('purchases.reception.store');
    Route::get('achats/{purchase}/reception/{reception}',  [ErpReceptionController::class, 'show'])->name('purchases.reception.show');

    // ─── Rapports ─────────────────────────────────────────────────────────────
    Route::prefix('rapports')->name('reports.')->group(function () {
        Route::get('valorisation-stock',             [ErpReportController::class, 'stockValuationReport'])->name('stock-valuation');
        Route::get('achats',                         [ErpReportController::class, 'purchasesReport'])->name('purchases');
        Route::get('mouvements-stock',               [ErpReportController::class, 'stockMovementsReport'])->name('stock-movements');
        Route::get('suggestions-reapprovisionnement',[ErpReportController::class, 'replenishmentSuggestions'])->name('replenishment-suggestions');
    });

});

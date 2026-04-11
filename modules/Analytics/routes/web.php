<?php

use Illuminate\Support\Facades\Route;
use Modules\Analytics\Http\Controllers\AnalyticsDashboardController;
use Modules\Analytics\Http\Controllers\AnalyticsExportController;

/*
|--------------------------------------------------------------------------
| Analytics Module Routes
|--------------------------------------------------------------------------
| Module Business Intelligence - Insights & Analytics temps réel
*/

Route::prefix('analytics')
    ->middleware(['web', 'auth', 'admin'])
    ->name('analytics.')
    ->group(function () {
        
        // Dashboard principal
        Route::get('/', [AnalyticsDashboardController::class, 'index'])->name('dashboard');

        // API Endpoints pour les graphiques (AJAX)
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('/revenue-chart', [AnalyticsDashboardController::class, 'revenueChart'])->name('revenue-chart');
            Route::get('/orders-status', [AnalyticsDashboardController::class, 'ordersStatusChart'])->name('orders-status');
            Route::get('/category-chart', [AnalyticsDashboardController::class, 'categoryChart'])->name('category-chart');
            Route::get('/monthly-chart', [AnalyticsDashboardController::class, 'monthlyChart'])->name('monthly-chart');
            Route::get('/peak-hours', [AnalyticsDashboardController::class, 'peakHoursChart'])->name('peak-hours');
            Route::get('/kpis', [AnalyticsDashboardController::class, 'realTimeKpis'])->name('kpis');
            Route::get('/insights', [AnalyticsDashboardController::class, 'insights'])->name('insights');
        });

        // Export Rapports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('/report', [AnalyticsExportController::class, 'exportReport'])->name('report');
            Route::get('/json', [AnalyticsExportController::class, 'exportJson'])->name('json');
            Route::get('/csv', [AnalyticsExportController::class, 'exportCsv'])->name('csv');
        });
    });


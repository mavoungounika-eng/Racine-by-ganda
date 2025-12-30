<?php

use Illuminate\Support\Facades\Route;
use Modules\Frontend\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Module Frontend - Routes Web
|--------------------------------------------------------------------------
|
| Routes pour les dashboards par rôle
|
*/

Route::middleware('auth')->prefix('dashboard')->name('dashboard.')->group(function () {
    
    // Dashboard Super Admin
    Route::get('/super-admin', [DashboardController::class, 'superAdmin'])
        ->name('super-admin');
    
    // Dashboard Admin
    Route::get('/admin', [DashboardController::class, 'admin'])
        ->name('admin');
    
    // Dashboard Staff
    Route::get('/staff', [DashboardController::class, 'staff'])
        ->name('staff');
    
    // Dashboard Créateur - DÉSACTIVÉ : Utiliser /createur/dashboard (creator.dashboard) à la place
    // Route::get('/createur', [DashboardController::class, 'createur'])
    //     ->name('createur');
    
    // Redirection vers le nouveau dashboard créateur
    Route::get('/createur', function() {
        return redirect()->route('creator.dashboard');
    })->name('createur');
    
    // Dashboard Client - DÉSACTIVÉ : Utiliser /compte (account.dashboard) à la place
    // Route::get('/client', [DashboardController::class, 'client'])
    //     ->name('client');
    
});

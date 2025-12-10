<?php

use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CrmDashboardController;
use Modules\CRM\Http\Controllers\CrmContactController;
use Modules\CRM\Http\Controllers\CrmOpportunityController;

/*
|--------------------------------------------------------------------------
| Module CRM - Routes Web
|--------------------------------------------------------------------------
|
| Routes pour le module CRM (Gestion des contacts, opportunités, etc.)
| Accessible uniquement aux rôles : staff, admin, super_admin
|
*/

Route::prefix('crm')->name('crm.')->middleware(['auth', 'can:access-crm'])->group(function () {
    
    // Dashboard CRM
    Route::get('/', [CrmDashboardController::class, 'index'])->name('dashboard');
    
    // Gestion des Contacts
    Route::get('/contacts/export', [CrmContactController::class, 'export'])->name('contacts.export');
    Route::resource('contacts', CrmContactController::class)->names([
        'index' => 'contacts.index',
        'create' => 'contacts.create',
        'store' => 'contacts.store',
        'show' => 'contacts.show',
        'edit' => 'contacts.edit',
        'update' => 'contacts.update',
        'destroy' => 'contacts.destroy',
    ]);
    
    // Gestion des Opportunités
    Route::resource('opportunites', CrmOpportunityController::class)->names([
        'index' => 'opportunities.index',
        'create' => 'opportunities.create',
        'store' => 'opportunities.store',
        'show' => 'opportunities.show',
        'edit' => 'opportunities.edit',
        'update' => 'opportunities.update',
        'destroy' => 'opportunities.destroy',
    ]);

    // Interactions
    Route::get('/interactions', [\Modules\CRM\Http\Controllers\CrmInteractionController::class, 'index'])->name('interactions.index');
    Route::post('contacts/{contact}/interactions', [\Modules\CRM\Http\Controllers\CrmInteractionController::class, 'store'])->name('interactions.store');
    Route::delete('/interactions/{interaction}', [\Modules\CRM\Http\Controllers\CrmInteractionController::class, 'destroy'])->name('interactions.destroy');
    
});

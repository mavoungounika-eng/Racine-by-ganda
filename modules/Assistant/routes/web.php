<?php

use Illuminate\Support\Facades\Route;
use Modules\Assistant\Http\Controllers\AmiraController;

/*
|--------------------------------------------------------------------------
| Module Assistant (Amira) - Routes Web
|--------------------------------------------------------------------------
|
| Routes pour l'assistant IA
|
*/

Route::prefix('amira')->name('amira.')->group(function () {
    
    // Route API pour envoyer un message (AJAX)
    Route::post('/message', [AmiraController::class, 'sendMessage'])
        ->name('message');
    
    // Route pour effacer l'historique
    Route::post('/clear', [AmiraController::class, 'clearHistory'])
        ->name('clear');
    
    // Route pour le statut d'Amira
    Route::get('/status', [AmiraController::class, 'status'])
        ->name('status');

    // Route de test pour voir le widget seul (dev)
    Route::get('/test-widget', function () {
        return view('assistant::chat');
    });
    
});

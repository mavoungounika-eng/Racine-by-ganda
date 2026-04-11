<?php

use Illuminate\Support\Facades\Route;
use Modules\Assistant\Http\Controllers\AmiraController;

/*
|--------------------------------------------------------------------------
| Module Assistant - API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('amira')->name('api.amira.')->group(function () {
    Route::post('/ask', [AmiraController::class, 'sendMessage'])->name('ask');
});

<?php

/*
|--------------------------------------------------------------------------
| Module Auth - Routes Web
|--------------------------------------------------------------------------
|
| NOTE: Ce module a été désactivé car il créait des doublons avec
| les contrôleurs principaux d'authentification.
|
| Authentification utilisée :
| - PublicAuthController : /login (Clients & Créateurs)
| - AdminAuthController : /admin/login (Administrateurs)
| - ErpAuthController : /erp/login (Staff ERP)
|
| Les contrôleurs ClientAuthController et EquipeAuthController
| ont été supprimés car ils étaient des doublons.
|
*/

// Routes désactivées - Utiliser les contrôleurs principaux
// Route::prefix('login-client')->name('auth.client.')->middleware('guest')->group(function () {
//     Route::get('/', [ClientAuthController::class, 'showLoginForm'])->name('login');
//     Route::post('/', [ClientAuthController::class, 'login'])->name('login.post');
//     Route::get('/inscription', [ClientAuthController::class, 'showRegisterForm'])->name('register');
//     Route::post('/inscription', [ClientAuthController::class, 'register'])->name('register.post');
// });

// Route::prefix('login-equipe')->name('auth.equipe.')->middleware('guest')->group(function () {
//     Route::get('/', [EquipeAuthController::class, 'showLoginForm'])->name('login');
//     Route::post('/', [EquipeAuthController::class, 'login'])->name('login.post');
// });

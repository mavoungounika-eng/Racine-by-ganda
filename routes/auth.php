<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\AuthHubController;
use App\Http\Controllers\Auth\PublicAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\SocialAuthController;

/*
|--------------------------------------------------------------------------
| Routes d'Authentification Unifiées
|--------------------------------------------------------------------------
|
| Toutes les routes d'authentification sont centralisées ici.
| Le système utilise un seul guard 'web' pour tous les utilisateurs.
| Les redirections sont gérées automatiquement selon le rôle.
|
*/

// ============================================
// HUB D'AUTHENTIFICATION
// ============================================
Route::get('/auth', [AuthHubController::class, 'index'])->name('auth.hub');

// ============================================
// CONNEXION UNIFIÉE
// ============================================
Route::middleware('guest')->group(function () {
    // Formulaire de connexion
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    
    // Traitement de la connexion (rate limiting: 5 tentatives par minute)
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');
});

// ============================================
// INSCRIPTION (Client & Créateur)
// ============================================
Route::middleware('guest')->group(function () {
    Route::get('/register', [PublicAuthController::class, 'showRegisterForm'])->name('register');
    // Rate limiting: 3 inscriptions par heure
    Route::post('/register', [PublicAuthController::class, 'register'])
        ->middleware('throttle:3,60')
        ->name('register.post');
});

// ============================================
// RÉINITIALISATION DE MOT DE PASSE
// ============================================
Route::middleware('guest')->group(function () {
    Route::get('/password/forgot', [PublicAuthController::class, 'showForgotForm'])->name('password.request');
    // Rate limiting: 3 demandes par heure
    Route::post('/password/email', [PublicAuthController::class, 'sendResetLink'])
        ->middleware('throttle:3,60')
        ->name('password.email');
    
    Route::get('/password/reset/{token}', [PublicAuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [PublicAuthController::class, 'reset'])->name('password.update');
});

// ============================================
// DÉCONNEXION
// ============================================
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ============================================
// CONNEXION GOOGLE (Social Login) - Module v1
// ============================================
// PHASE 2.1 : Route avec paramètre role optionnel (client|creator)
Route::get('/auth/google/redirect/{role?}', [GoogleAuthController::class, 'redirect'])
    ->where('role', 'client|creator')
    ->name('auth.google.redirect');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');

// ============================================
// CONNEXION SOCIALE MULTI-PROVIDERS (Social Auth v2)
// ============================================
// Routes génériques pour Google, Apple, Facebook
// Module Social Auth v2 - Indépendant du module Google Auth v1
Route::get('/auth/{provider}/redirect/{role?}', [SocialAuthController::class, 'redirect'])
    ->where('provider', 'google|apple|facebook')
    ->where('role', 'client|creator')
    ->name('auth.social.redirect');

Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->where('provider', 'google|apple|facebook')
    ->name('auth.social.callback');


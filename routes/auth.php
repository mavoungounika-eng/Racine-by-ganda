<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PublicAuthController;
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
// PHASE 1 SÉCURITÉ : Le hub redirige maintenant vers /login directement
// La carte "Espace Équipe" a été masquée pour réduire la surface d'attaque
Route::get('/auth', function () {
    return redirect()->route('login');
})->name('auth.hub');

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
// CONNEXION ADMIN/ÉQUIPE (PHASE 1 SÉCURITÉ)
// ============================================
// Route dédiée pour l'espace équipe, non exposée publiquement
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\AdminLoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AdminLoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');
});

// ============================================
// DÉCONNEXION
// ============================================
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

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


// ============================================
// CGU - Acceptation obligatoire
// ============================================
Route::middleware('auth')->group(function () {
    Route::get('/terms/accept', [\App\Http\Controllers\Auth\TermsController::class, 'show'])->name('terms.accept');
    Route::post('/terms/accept', [\App\Http\Controllers\Auth\TermsController::class, 'accept'])->name('terms.accept.post');
});

// ============================================
// VÉRIFICATION EMAIL
// ============================================
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\CMS\Http\Controllers\CmsApiController;

/*
|--------------------------------------------------------------------------
| API Routes - CMS Module
|--------------------------------------------------------------------------
|
| Routes API REST pour le module CMS
|
*/

Route::prefix('cms')->name('cms.api.')->middleware(['api', 'auth:sanctum'])->group(function () {
    
    // Pages
    Route::get('/pages', [CmsApiController::class, 'pages'])->name('pages.index');
    Route::get('/pages/{page}', [CmsApiController::class, 'showPage'])->name('pages.show');
    Route::post('/pages', [CmsApiController::class, 'storePage'])->name('pages.store');
    Route::put('/pages/{page}', [CmsApiController::class, 'updatePage'])->name('pages.update');
    Route::delete('/pages/{page}', [CmsApiController::class, 'destroyPage'])->name('pages.destroy');
    
    // Events
    Route::get('/events', [CmsApiController::class, 'events'])->name('events.index');
    Route::get('/events/{event}', [CmsApiController::class, 'showEvent'])->name('events.show');
    Route::post('/events', [CmsApiController::class, 'storeEvent'])->name('events.store');
    Route::put('/events/{event}', [CmsApiController::class, 'updateEvent'])->name('events.update');
    Route::delete('/events/{event}', [CmsApiController::class, 'destroyEvent'])->name('events.destroy');
    
    // Portfolio
    Route::get('/portfolio', [CmsApiController::class, 'portfolio'])->name('portfolio.index');
    Route::get('/portfolio/{portfolio}', [CmsApiController::class, 'showPortfolio'])->name('portfolio.show');
    Route::post('/portfolio', [CmsApiController::class, 'storePortfolio'])->name('portfolio.store');
    Route::put('/portfolio/{portfolio}', [CmsApiController::class, 'updatePortfolio'])->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', [CmsApiController::class, 'destroyPortfolio'])->name('portfolio.destroy');
    
    // Albums
    Route::get('/albums', [CmsApiController::class, 'albums'])->name('albums.index');
    Route::get('/albums/{album}', [CmsApiController::class, 'showAlbum'])->name('albums.show');
    Route::post('/albums', [CmsApiController::class, 'storeAlbum'])->name('albums.store');
    Route::put('/albums/{album}', [CmsApiController::class, 'updateAlbum'])->name('albums.update');
    Route::delete('/albums/{album}', [CmsApiController::class, 'destroyAlbum'])->name('albums.destroy');
    
    // Banners
    Route::get('/banners', [CmsApiController::class, 'banners'])->name('banners.index');
    Route::get('/banners/{banner}', [CmsApiController::class, 'showBanner'])->name('banners.show');
    Route::post('/banners', [CmsApiController::class, 'storeBanner'])->name('banners.store');
    Route::put('/banners/{banner}', [CmsApiController::class, 'updateBanner'])->name('banners.update');
    Route::delete('/banners/{banner}', [CmsApiController::class, 'destroyBanner'])->name('banners.destroy');
    
    // Blocks
    Route::get('/blocks', [CmsApiController::class, 'blocks'])->name('blocks.index');
    Route::get('/blocks/{block}', [CmsApiController::class, 'showBlock'])->name('blocks.show');
    Route::post('/blocks', [CmsApiController::class, 'storeBlock'])->name('blocks.store');
    Route::put('/blocks/{block}', [CmsApiController::class, 'updateBlock'])->name('blocks.update');
    Route::delete('/blocks/{block}', [CmsApiController::class, 'destroyBlock'])->name('blocks.destroy');
    
    // FAQ
    Route::get('/faq', [CmsApiController::class, 'faq'])->name('faq.index');
    Route::get('/faq/{faq}', [CmsApiController::class, 'showFaq'])->name('faq.show');
    Route::post('/faq', [CmsApiController::class, 'storeFaq'])->name('faq.store');
    Route::put('/faq/{faq}', [CmsApiController::class, 'updateFaq'])->name('faq.update');
    Route::delete('/faq/{faq}', [CmsApiController::class, 'destroyFaq'])->name('faq.destroy');
    
    // Categories FAQ
    Route::get('/faq/categories', [CmsApiController::class, 'faqCategories'])->name('faq.categories.index');
    Route::post('/faq/categories', [CmsApiController::class, 'storeFaqCategory'])->name('faq.categories.store');
    Route::put('/faq/categories/{category}', [CmsApiController::class, 'updateFaqCategory'])->name('faq.categories.update');
    Route::delete('/faq/categories/{category}', [CmsApiController::class, 'destroyFaqCategory'])->name('faq.categories.destroy');
    
});

// Upload d'image (accessible depuis TinyMCE sans auth sanctum, mais avec CSRF)
Route::post('/cms/upload-image', [CmsApiController::class, 'uploadImage'])
    ->middleware(['web'])
    ->name('cms.api.upload-image');


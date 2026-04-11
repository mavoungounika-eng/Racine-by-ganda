<?php

use Illuminate\Support\Facades\Route;
use Modules\CMS\Http\Controllers\CmsAdminController;

// Routes Admin CMS (protégées par middleware admin)
Route::prefix('admin/cms')->name('cms.admin.')->middleware(['web', 'auth', 'admin'])->group(function () {
    // Dashboard CMS
    Route::get('/', [CmsAdminController::class, 'index'])->name('dashboard');
    
    // Pages
    Route::get('/pages', [CmsAdminController::class, 'pages'])->name('pages');
    Route::get('/pages/create', [CmsAdminController::class, 'createPage'])->name('pages.create');
    Route::post('/pages', [CmsAdminController::class, 'storePage'])->name('pages.store');
    Route::get('/pages/{page}/edit', [CmsAdminController::class, 'editPage'])->name('pages.edit');
    Route::put('/pages/{page}', [CmsAdminController::class, 'updatePage'])->name('pages.update');
    Route::delete('/pages/{page}', [CmsAdminController::class, 'destroyPage'])->name('pages.destroy');
    
    // Events
    Route::get('/events', [CmsAdminController::class, 'events'])->name('events');
    Route::get('/events/create', [CmsAdminController::class, 'createEvent'])->name('events.create');
    Route::post('/events', [CmsAdminController::class, 'storeEvent'])->name('events.store');
    Route::get('/events/{event}/edit', [CmsAdminController::class, 'editEvent'])->name('events.edit');
    Route::put('/events/{event}', [CmsAdminController::class, 'updateEvent'])->name('events.update');
    Route::delete('/events/{event}', [CmsAdminController::class, 'destroyEvent'])->name('events.destroy');
    
    // Portfolio
    Route::get('/portfolio', [CmsAdminController::class, 'portfolio'])->name('portfolio');
    Route::get('/portfolio/create', [CmsAdminController::class, 'createPortfolio'])->name('portfolio.create');
    Route::post('/portfolio', [CmsAdminController::class, 'storePortfolio'])->name('portfolio.store');
    Route::get('/portfolio/{portfolio}/edit', [CmsAdminController::class, 'editPortfolio'])->name('portfolio.edit');
    Route::put('/portfolio/{portfolio}', [CmsAdminController::class, 'updatePortfolio'])->name('portfolio.update');
    Route::delete('/portfolio/{portfolio}', [CmsAdminController::class, 'destroyPortfolio'])->name('portfolio.destroy');
    
    // Albums
    Route::get('/albums', [CmsAdminController::class, 'albums'])->name('albums');
    Route::get('/albums/create', [CmsAdminController::class, 'createAlbum'])->name('albums.create');
    Route::post('/albums', [CmsAdminController::class, 'storeAlbum'])->name('albums.store');
    Route::get('/albums/{album}/edit', [CmsAdminController::class, 'editAlbum'])->name('albums.edit');
    Route::put('/albums/{album}', [CmsAdminController::class, 'updateAlbum'])->name('albums.update');
    Route::delete('/albums/{album}', [CmsAdminController::class, 'destroyAlbum'])->name('albums.destroy');
    
    // Banners
    Route::get('/banners', [CmsAdminController::class, 'banners'])->name('banners');
    Route::get('/banners/create', [CmsAdminController::class, 'createBanner'])->name('banners.create');
    Route::post('/banners', [CmsAdminController::class, 'storeBanner'])->name('banners.store');
    Route::get('/banners/{banner}/edit', [CmsAdminController::class, 'editBanner'])->name('banners.edit');
    Route::put('/banners/{banner}', [CmsAdminController::class, 'updateBanner'])->name('banners.update');
    Route::delete('/banners/{banner}', [CmsAdminController::class, 'destroyBanner'])->name('banners.destroy');
    
    // Blocks
    Route::get('/blocks', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'index'])->name('blocks.index');
    Route::get('/blocks/create', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'create'])->name('blocks.create');
    Route::post('/blocks', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'store'])->name('blocks.store');
    Route::get('/blocks/{block}/edit', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'edit'])->name('blocks.edit');
    Route::put('/blocks/{block}', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'update'])->name('blocks.update');
    Route::delete('/blocks/{block}', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'destroy'])->name('blocks.destroy');
    Route::patch('/blocks/{block}/toggle', [\Modules\CMS\Http\Controllers\CmsBlockController::class, 'toggle'])->name('blocks.toggle');
    
    // FAQ
    Route::get('/faq', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'index'])->name('faq.index');
    Route::get('/faq/create', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'create'])->name('faq.create');
    Route::post('/faq', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'store'])->name('faq.store');
    Route::get('/faq/{faq}/edit', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'edit'])->name('faq.edit');
    Route::put('/faq/{faq}', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'update'])->name('faq.update');
    Route::delete('/faq/{faq}', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'destroy'])->name('faq.destroy');
    Route::get('/faq/categories', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'categories'])->name('faq.categories');
    Route::post('/faq/categories', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'storeCategory'])->name('faq.category.store');
    Route::put('/faq/categories/{category}', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'updateCategory'])->name('faq.category.update');
    Route::delete('/faq/categories/{category}', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'destroyCategory'])->name('faq.category.destroy');
    
    // Settings
    Route::get('/settings', [CmsAdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [CmsAdminController::class, 'updateSettings'])->name('settings.update');
});

// Routes publiques CMS (pour affichage frontend)
Route::prefix('cms')->name('cms.')->middleware(['web'])->group(function () {
    Route::get('/page/{slug}', [\Modules\CMS\Http\Controllers\CmsPublicController::class, 'showPage'])->name('page.show');
    Route::get('/event/{slug}', [\Modules\CMS\Http\Controllers\CmsPublicController::class, 'showEvent'])->name('event.show');
    Route::get('/portfolio/{slug}', [\Modules\CMS\Http\Controllers\CmsPublicController::class, 'showPortfolio'])->name('portfolio.show');
    Route::get('/album/{slug}', [\Modules\CMS\Http\Controllers\CmsPublicController::class, 'showAlbum'])->name('album.show');
    Route::get('/faq', [\Modules\CMS\Http\Controllers\CmsFaqController::class, 'publicIndex'])->name('faq.public');
});


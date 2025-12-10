<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Front\FrontendController;
use App\Http\Controllers\Auth\AuthHubController;
use App\Http\Controllers\Auth\PublicAuthController;
use App\Http\Controllers\Auth\ErpAuthController;
use App\Http\Controllers\AppearanceController;

// ============================================
// AUTH ROUTES (Unifiées)
// ============================================
// Toutes les routes d'authentification sont maintenant dans routes/auth.php
require __DIR__.'/auth.php';

// ============================================
// AUTH CRÉATEUR (Espace Créateur / Vendeur)
// ============================================
use App\Http\Controllers\Creator\Auth\CreatorAuthController;
use App\Http\Controllers\Creator\CreatorDashboardController;

Route::prefix('createur')->name('creator.')->group(function () {
    // Routes publiques (guest)
    Route::middleware('guest')->group(function () {
        Route::get('login', [CreatorAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [CreatorAuthController::class, 'login'])->name('login.post');
        
        Route::get('register', [CreatorAuthController::class, 'showRegisterForm'])->name('register');
        Route::post('register', [CreatorAuthController::class, 'register'])->name('register.post');
    });

    // Déconnexion
    Route::post('logout', [CreatorAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    // Pages de statut
    Route::middleware('auth')->group(function () {
        Route::get('pending', function () {
            return view('creator.auth.pending');
        })->name('pending');
        
        Route::get('suspended', function () {
            return view('creator.auth.suspended');
        })->name('suspended');
    });

    // Routes protégées (créateur actif)
    Route::middleware(['auth', 'role.creator', 'creator.active'])->group(function () {
        Route::get('dashboard', [CreatorDashboardController::class, 'index'])->name('dashboard');
        
        // Produits
        Route::get('produits', [\App\Http\Controllers\Creator\CreatorProductController::class, 'index'])->name('products.index');
        Route::get('produits/nouveau', [\App\Http\Controllers\Creator\CreatorProductController::class, 'create'])->name('products.create');
        Route::post('produits', [\App\Http\Controllers\Creator\CreatorProductController::class, 'store'])->name('products.store');
        Route::get('produits/{product}/edit', [\App\Http\Controllers\Creator\CreatorProductController::class, 'edit'])->name('products.edit');
        Route::put('produits/{product}', [\App\Http\Controllers\Creator\CreatorProductController::class, 'update'])->name('products.update');
        Route::delete('produits/{product}', [\App\Http\Controllers\Creator\CreatorProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('produits/{product}/publier', [\App\Http\Controllers\Creator\CreatorProductController::class, 'publish'])->name('products.publish');
        
        // Commandes
        Route::get('commandes', [\App\Http\Controllers\Creator\CreatorOrderController::class, 'index'])->name('orders.index');
        Route::get('commandes/{order}', [\App\Http\Controllers\Creator\CreatorOrderController::class, 'show'])->name('orders.show');
        Route::patch('commandes/{order}/statut', [\App\Http\Controllers\Creator\CreatorOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        
        // Finances
        Route::get('finances', [\App\Http\Controllers\Creator\CreatorFinanceController::class, 'index'])->name('finances.index');
        
        // Statistiques avancées
        Route::get('stats', [\App\Http\Controllers\Creator\CreatorStatsController::class, 'index'])->name('stats.index');
        
        // Exports et Rapports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('orders', [\App\Http\Controllers\Creator\CreatorExportController::class, 'exportOrders'])->name('orders');
            Route::get('products', [\App\Http\Controllers\Creator\CreatorExportController::class, 'exportProducts'])->name('products');
            Route::get('finances', [\App\Http\Controllers\Creator\CreatorExportController::class, 'exportFinancialReport'])->name('finances');
        });
        
        // Notifications
        Route::get('notifications', [\App\Http\Controllers\Creator\CreatorNotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/marquer-lu', [\App\Http\Controllers\Creator\CreatorNotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::patch('notifications/marquer-tout-lu', [\App\Http\Controllers\Creator\CreatorNotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        
        // Profil (route legacy - redirige vers la route unifiée)
        Route::get('profil', function () {
            return redirect()->route('profile.edit');
        })->name('creator.profile.edit');
    });
});

// ============================================
// 2FA (Double Authentification)
// ============================================
use App\Http\Controllers\Auth\TwoFactorController;

// Challenge 2FA (lors de la connexion)
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');

// Gestion 2FA (utilisateur connecté)
Route::middleware('auth')->prefix('2fa')->name('2fa.')->group(function () {
    Route::get('/setup', [TwoFactorController::class, 'setup'])->name('setup');
    Route::post('/confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
    Route::get('/manage', [TwoFactorController::class, 'manage'])->name('manage');
    Route::post('/disable', [TwoFactorController::class, 'disable'])->name('disable');
    Route::post('/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('recovery-codes.regenerate');
});

// ============================================
// ROUTES ERP (Désactivées temporairement - utiliser /login)
// ============================================
// Les routes ERP sont désactivées. Utiliser /login pour tous les utilisateurs.
// Route::prefix('erp')->name('erp.')->group(function () {
//     Route::middleware('guest')->group(function () {
//         Route::get('/login', [ErpAuthController::class, 'showLoginForm'])->name('login');
//         Route::post('/login', [ErpAuthController::class, 'login'])->name('login.post');
//     });
//     Route::post('/logout', [ErpAuthController::class, 'logout'])->name('logout')->middleware('auth');
// });

// ============================================
// DASHBOARDS PAR RÔLE
// ============================================
Route::middleware('auth')->group(function () {
    // Dashboard Client - Route principale (utiliser celle-ci uniquement)
    Route::get('/compte', [\App\Http\Controllers\Account\ClientAccountController::class, 'index'])
        ->name('account.dashboard');
    
    // Redirection depuis l'ancienne route du module Frontend vers la route principale
    Route::get('/dashboard/client', function() {
        return redirect()->route('account.dashboard');
    })->name('dashboard.client.redirect');
    
    // Dashboard Créateur (route legacy - redirige vers la nouvelle route)
    // ⚠️ Route obsolète : /atelier-creator mélangeait "atelier" (marque) et "creator" (marketplace)
    // Utiliser /createur/dashboard à la place
    Route::get('/atelier-creator', function() {
        return redirect()->route('creator.dashboard');
    })->name('creator.dashboard.legacy')->middleware('role.creator');
    
    // Dashboard Staff (temporaire - à implémenter)
    Route::get('/staff/dashboard', function() {
        return view('admin.dashboard'); // Utiliser le dashboard admin pour l'instant
    })->name('staff.dashboard')->middleware('staff');
    
    // Routes Profil (Phase 7) - Unifiées pour tous les rôles
    Route::get('/profil', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');
    Route::get('/profil/edit', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [\App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('/profil/commandes', [\App\Http\Controllers\ProfileController::class, 'orders'])->name('profile.orders');
    Route::get('/profil/commandes/{order}', [\App\Http\Controllers\ProfileController::class, 'showOrder'])->name('profile.orders.show');
    Route::get('/profil/adresses', [\App\Http\Controllers\ProfileController::class, 'addresses'])->name('profile.addresses');
    Route::post('/profil/adresses', [\App\Http\Controllers\ProfileController::class, 'storeAddress'])->name('profile.addresses.store');
    Route::delete('/profil/adresses/{address}', [\App\Http\Controllers\ProfileController::class, 'deleteAddress'])->name('profile.addresses.delete');
    Route::get('/profil/fidelite', [\App\Http\Controllers\ProfileController::class, 'loyalty'])->name('profile.loyalty');
    Route::post('/profil/verify-email', [\App\Http\Controllers\ProfileController::class, 'verifyProfessionalEmail'])->name('profile.verify-email');
    
    // Favoris
    Route::get('/profil/favoris', [\App\Http\Controllers\Profile\WishlistController::class, 'index'])->name('profile.wishlist');
    Route::post('/profil/favoris/add', [\App\Http\Controllers\Profile\WishlistController::class, 'add'])->name('profile.wishlist.add');
    Route::delete('/profil/favoris/remove/{id}', [\App\Http\Controllers\Profile\WishlistController::class, 'remove'])->name('profile.wishlist.remove');
    Route::post('/profil/favoris/toggle', [\App\Http\Controllers\Profile\WishlistController::class, 'toggle'])->name('profile.wishlist.toggle');
    Route::post('/profil/favoris/clear', [\App\Http\Controllers\Profile\WishlistController::class, 'clear'])->name('profile.wishlist.clear');

    // Reviews (Profile)
    Route::get('/profil/avis', [\App\Http\Controllers\Profile\ReviewController::class, 'index'])->name('profile.reviews');
    Route::get('/profil/commandes/{order}/avis', [\App\Http\Controllers\Profile\ReviewController::class, 'create'])->name('profile.reviews.create');
    Route::post('/profil/avis', [\App\Http\Controllers\Profile\ReviewController::class, 'store'])->name('profile.reviews.store');
    Route::get('/profil/avis/{review}/edit', [\App\Http\Controllers\Profile\ReviewController::class, 'edit'])->name('profile.reviews.edit');
    Route::put('/profil/avis/{review}', [\App\Http\Controllers\Profile\ReviewController::class, 'update'])->name('profile.reviews.update');
    Route::delete('/profil/avis/{review}', [\App\Http\Controllers\Profile\ReviewController::class, 'destroy'])->name('profile.reviews.destroy');
    
    // Reviews (Frontend - depuis produit)
    Route::post('/products/{product}/reviews', [\App\Http\Controllers\Front\ReviewController::class, 'store'])->name('reviews.store');
    
    // Factures
    Route::get('/profil/commandes/{order}/facture', [\App\Http\Controllers\Profile\InvoiceController::class, 'show'])->name('profile.invoice.show');
    Route::get('/profil/commandes/{order}/facture/download', [\App\Http\Controllers\Profile\InvoiceController::class, 'download'])->name('profile.invoice.download');
    Route::get('/profil/commandes/{order}/facture/print', [\App\Http\Controllers\Profile\InvoiceController::class, 'print'])->name('profile.invoice.print');
    
    // Export Données RGPD
    Route::get('/profil/export-donnees', [\App\Http\Controllers\Profile\DataExportController::class, 'export'])->name('profile.data.export');
    Route::get('/profil/supprimer-compte', [\App\Http\Controllers\Profile\DataExportController::class, 'showDeleteAccount'])->name('profile.delete-account');
    Route::delete('/profil/supprimer-compte', [\App\Http\Controllers\Profile\DataExportController::class, 'deleteAccount'])->name('profile.delete-account.destroy');
    
    // Routes Apparence
    Route::get('/appearance/settings', [AppearanceController::class, 'index'])->name('appearance.settings');
    Route::post('/appearance/update', [AppearanceController::class, 'update'])->name('appearance.update');
    Route::post('/appearance/update-single', [AppearanceController::class, 'updateSingle'])->name('appearance.update.single');
    Route::post('/appearance/reset', [AppearanceController::class, 'reset'])->name('appearance.reset');
    Route::get('/appearance/current', [AppearanceController::class, 'current'])->name('appearance.current');
    Route::post('/appearance/preview', [AppearanceController::class, 'preview'])->name('appearance.preview');

    // Routes Notifications (Phase 10)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::get('/count', [\App\Http\Controllers\NotificationController::class, 'count'])->name('count');
        Route::post('/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        Route::delete('/clear/read', [\App\Http\Controllers\NotificationController::class, 'deleteRead'])->name('delete-read');
    });

    // Messagerie
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MessageController::class, 'index'])->name('index');
        Route::get('/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('unread-count');
        Route::post('/create-direct', [\App\Http\Controllers\MessageController::class, 'createDirect'])->name('create-direct');
        Route::post('/create-order-thread/{order}', [\App\Http\Controllers\MessageController::class, 'createOrderThread'])->name('create-order-thread');
        Route::post('/create-product-thread/{product}', [\App\Http\Controllers\MessageController::class, 'createProductThread'])->name('create-product-thread');
        Route::get('/{id}', [\App\Http\Controllers\MessageController::class, 'show'])->name('show');
        Route::get('/{id}/messages', [\App\Http\Controllers\MessageController::class, 'getMessages'])->name('get-messages');
        // Rate limiting: 10 messages par minute
    Route::post('/{id}/send', [\App\Http\Controllers\MessageController::class, 'sendMessage'])
        ->middleware('throttle:10,1')
        ->name('send');
        Route::put('/{id}/archive', [\App\Http\Controllers\MessageController::class, 'archive'])->name('archive');
        Route::put('/{id}/unarchive', [\App\Http\Controllers\MessageController::class, 'unarchive'])->name('unarchive');
        Route::put('/message/{messageId}/edit', [\App\Http\Controllers\MessageController::class, 'editMessage'])->name('edit-message');
        Route::delete('/message/{messageId}', [\App\Http\Controllers\MessageController::class, 'deleteMessage'])->name('delete-message');
        Route::post('/{conversation}/tag-product', [\App\Http\Controllers\MessageController::class, 'tagProduct'])->name('tag-product');
        Route::delete('/{conversation}/untag-product/{product}', [\App\Http\Controllers\MessageController::class, 'untagProduct'])->name('untag-product');
        Route::post('/{conversation}/send-email', [\App\Http\Controllers\MessageController::class, 'sendEmail'])->name('send-email');
    });
});

// ============================================
// FRONTEND ROUTES
// ============================================


// Changement de langue
Route::get('/language/{locale}', [\App\Http\Controllers\LanguageController::class, 'switch'])->name('language.switch');

// Routes Frontend (Rate Limited: 60 req/min)
Route::middleware('throttle:60,1')->name('frontend.')->group(function () {
    Route::get('/', [FrontendController::class, 'home'])->name('home');
    Route::get('/boutique', [FrontendController::class, 'shop'])->name('shop');
    Route::get('/search', [\App\Http\Controllers\Front\SearchController::class, 'index'])->name('search');
    Route::get('/api/search/suggest', [\App\Http\Controllers\Front\SearchController::class, 'suggest'])->name('search.suggest');
    Route::get('/showroom', [FrontendController::class, 'showroom'])->name('showroom');
    Route::get('/atelier', [FrontendController::class, 'atelier'])->name('atelier');
    Route::get('/contact', [FrontendController::class, 'contact'])->name('contact');
    Route::get('/produit/{id}', [FrontendController::class, 'product'])->name('product');
    Route::get('/createurs', [FrontendController::class, 'creators'])->name('creators');
    Route::get('/marketplace', [FrontendController::class, 'marketplace'])->name('marketplace');
    Route::get('/createurs/{slug}/boutique', [FrontendController::class, 'creatorShop'])->name('creator.shop');
    
    // Nouvelles pages
    Route::get('/evenements', [FrontendController::class, 'events'])->name('events');
    Route::get('/portfolio', [FrontendController::class, 'portfolio'])->name('portfolio');
    Route::get('/albums', [FrontendController::class, 'albums'])->name('albums');
    Route::get('/amira-ganda', [FrontendController::class, 'ceo'])->name('ceo');
    Route::get('/charte-graphique', [FrontendController::class, 'brandGuidelines'])->name('brand-guidelines');
    
    // Pages informatives
    Route::get('/aide', [FrontendController::class, 'help'])->name('help');
    Route::get('/livraison', [FrontendController::class, 'shipping'])->name('shipping');
    Route::get('/retours-echanges', [FrontendController::class, 'returns'])->name('returns');
    Route::get('/cgv', [FrontendController::class, 'terms'])->name('terms');
    Route::get('/confidentialite', [FrontendController::class, 'privacy'])->name('privacy');
    Route::get('/a-propos', [FrontendController::class, 'about'])->name('about');
});

// ============================================
// ROUTES ADMIN
// ============================================
Route::prefix('admin')->name('admin.')->group(function () {
    // Routes de login admin (désactivées - utiliser /login)
    // Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    // Route::post('login', [AdminAuthController::class, 'login'])->name('login.post');

    // Routes protégées par le middleware "admin"
    Route::middleware('admin')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

        // Gestion des utilisateurs
        Route::resource('users', AdminUserController::class);

        // Gestion des rôles
        Route::resource('roles', AdminRoleController::class)->except(['show']);

        // Gestion des catégories
        Route::resource('categories', \App\Http\Controllers\Admin\AdminCategoryController::class);

        // Gestion des produits
        Route::resource('products', \App\Http\Controllers\Admin\AdminProductController::class);

        // Gestion des commandes - Routes spécifiques AVANT la route resource
        Route::get('orders/scan', [\App\Http\Controllers\Admin\AdminOrderController::class, 'scanForm'])->name('orders.scan');
        Route::post('orders/scan', [\App\Http\Controllers\Admin\AdminOrderController::class, 'scanHandle'])->name('orders.scan.handle');
        Route::get('orders/{order}/qrcode', [\App\Http\Controllers\Admin\AdminOrderController::class, 'showQr'])->name('orders.qr');
        
        // Route resource pour les commandes (doit être APRÈS les routes spécifiques)
        Route::resource('orders', \App\Http\Controllers\Admin\AdminOrderController::class)->only(['index', 'show', 'update']);
        
        // Système POS (Point of Sale) - Boutique physique
        Route::prefix('pos')->name('pos.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('index');
            Route::post('search-product', [\App\Http\Controllers\Admin\PosController::class, 'searchProduct'])->name('search-product');
            Route::post('create-order', [\App\Http\Controllers\Admin\PosController::class, 'createOrder'])->name('create-order');
            Route::post('order/{order}/confirm-payment', [\App\Http\Controllers\Admin\PosController::class, 'confirmCardPayment'])->name('confirm-payment');
            Route::get('order/{order}', [\App\Http\Controllers\Admin\PosController::class, 'getOrder'])->name('order');
        });
        
        // Analytics / Dashboard
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('index');
            Route::get('/funnel', [\App\Http\Controllers\Admin\AnalyticsController::class, 'funnel'])->name('funnel');
            Route::get('/sales', [\App\Http\Controllers\Admin\AnalyticsController::class, 'sales'])->name('sales');
        });

        // Gestion des créateurs
        Route::get('creators', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'index'])->name('creators.index');
        Route::get('creators/{id}', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'show'])->name('creators.show');
        Route::patch('creators/{id}/verify', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'verify'])->name('creators.verify');
        Route::patch('creators/documents/{id}/verify', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'verifyDocument'])->name('creators.documents.verify');
        Route::post('creators/{id}/checklist/initialize', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'initializeChecklist'])->name('creators.checklist.initialize');
        Route::patch('creators/checklist/{id}/complete', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'completeChecklistItem'])->name('creators.checklist.complete');
        Route::patch('creators/checklist/{id}/uncomplete', [\App\Http\Controllers\Admin\AdminCreatorController::class, 'uncompleteChecklistItem'])->name('creators.checklist.uncomplete');
        
        // Notes internes
        Route::post('creators/{id}/notes', [\App\Http\Controllers\Admin\AdminCreatorNoteController::class, 'store'])->name('creators.notes.store');
        Route::put('creators/notes/{id}', [\App\Http\Controllers\Admin\AdminCreatorNoteController::class, 'update'])->name('creators.notes.update');
        Route::delete('creators/notes/{id}', [\App\Http\Controllers\Admin\AdminCreatorNoteController::class, 'destroy'])->name('creators.notes.destroy');
        
        // Export et rapports
        Route::get('creators/export/csv', [\App\Http\Controllers\Admin\AdminCreatorExportController::class, 'exportCsv'])->name('creators.export.csv');
        Route::get('creators/reports/validation', [\App\Http\Controllers\Admin\AdminCreatorExportController::class, 'validationReport'])->name('creators.reports.validation');
        
        // Finances
        Route::get('finances', [\App\Http\Controllers\Admin\AdminFinanceController::class, 'index'])->name('finances.index');
        
        // Statistiques
        Route::get('stats', [\App\Http\Controllers\Admin\AdminStatsController::class, 'index'])->name('stats.index');
        
        // Exports et Rapports
        Route::prefix('export')->name('export.')->group(function () {
            Route::get('orders', [\App\Http\Controllers\Admin\AdminExportController::class, 'exportOrders'])->name('orders');
            Route::get('users', [\App\Http\Controllers\Admin\AdminExportController::class, 'exportUsers'])->name('users');
            Route::get('products', [\App\Http\Controllers\Admin\AdminExportController::class, 'exportProducts'])->name('products');
            Route::get('financial-report', [\App\Http\Controllers\Admin\AdminExportController::class, 'exportFinancialReport'])->name('financial-report');
        });
        
        // Paramètres
        Route::get('settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [\App\Http\Controllers\Admin\AdminSettingsController::class, 'update'])->name('settings.update');
        
        // Notifications
        Route::get('notifications', [\App\Http\Controllers\Admin\AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('orders/{order}/qrcode', [\App\Http\Controllers\Admin\AdminOrderController::class, 'showQr'])->name('orders.qr');

        // Gestion des alertes de stock
        Route::get('stock-alerts', [\App\Http\Controllers\Admin\AdminStockAlertController::class, 'index'])->name('stock-alerts.index');
        Route::post('stock-alerts/{alert}/resolve', [\App\Http\Controllers\Admin\AdminStockAlertController::class, 'resolve'])->name('stock-alerts.resolve');
        Route::post('stock-alerts/{alert}/dismiss', [\App\Http\Controllers\Admin\AdminStockAlertController::class, 'dismiss'])->name('stock-alerts.dismiss');
        Route::post('stock-alerts/resolve-all', [\App\Http\Controllers\Admin\AdminStockAlertController::class, 'resolveAll'])->name('stock-alerts.resolve-all');

        // Gestion CMS - Routes migrées vers modules/CMS/routes/web.php
        // Utiliser les routes cms.admin.* du module CMS
    });
});

// Routes Front-end (Panier & Checkout) - Rate Limited: 120 req/min
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/cart', [\App\Http\Controllers\Front\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [\App\Http\Controllers\Front\CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [\App\Http\Controllers\Front\CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove', [\App\Http\Controllers\Front\CartController::class, 'remove'])->name('cart.remove');
    Route::get('/api/cart/count', [\App\Http\Controllers\Front\CartController::class, 'count'])->name('api.cart.count');
});

// Routes Checkout - Authentification requise + Vérification rôle client
Route::middleware(['auth', 'throttle:120,1'])->group(function () {
    // Checkout principal
    Route::get('/checkout', [\App\Http\Controllers\Front\CheckoutController::class, 'index'])->name('checkout.index');
    // Rate limiting: 10 commandes par minute
    Route::post('/checkout', [\App\Http\Controllers\Front\CheckoutController::class, 'placeOrder'])
        ->middleware('throttle:10,1')
        ->name('checkout.place');
    
    // Success / cancel génériques
    Route::get('/checkout/success/{order}', [\App\Http\Controllers\Front\CheckoutController::class, 'success'])
        ->name('checkout.success');
    Route::get('/checkout/cancel/{order}', [\App\Http\Controllers\Front\CheckoutController::class, 'cancel'])
        ->name('checkout.cancel');
    
    // Routes API pour validation temps réel checkout
    Route::post('/api/checkout/verify-stock', [\App\Http\Controllers\Front\CheckoutController::class, 'verifyStock'])->name('api.checkout.verify-stock');
    Route::post('/api/checkout/validate-email', [\App\Http\Controllers\Front\CheckoutController::class, 'validateEmail'])->name('api.checkout.validate-email');
    Route::post('/api/checkout/validate-phone', [\App\Http\Controllers\Front\CheckoutController::class, 'validatePhone'])->name('api.checkout.validate-phone');
    Route::post('/api/checkout/apply-promo', [\App\Http\Controllers\Front\CheckoutController::class, 'applyPromo'])->name('api.checkout.apply-promo');
});

// Routes Paiement
Route::middleware(['auth'])->group(function () {
    Route::post('/orders/{order}/pay', [\App\Http\Controllers\Front\PaymentController::class, 'pay'])->name('payment.pay');
    Route::get('/orders/{order}/payment/success', [\App\Http\Controllers\Front\PaymentController::class, 'success'])->name('payment.success');
    Route::get('/orders/{order}/payment/cancel', [\App\Http\Controllers\Front\PaymentController::class, 'cancel'])->name('payment.cancel');
    
    // Paiement par Carte Bancaire (Stripe)
    Route::post('/checkout/card/pay', [\App\Http\Controllers\Front\CardPaymentController::class, 'pay'])->name('checkout.card.pay');
    Route::get('/checkout/card/{order}/success', [\App\Http\Controllers\Front\CardPaymentController::class, 'success'])->name('checkout.card.success');
    Route::get('/checkout/card/{order}/cancel', [\App\Http\Controllers\Front\CardPaymentController::class, 'cancel'])->name('checkout.card.cancel');
    
    // Paiement Mobile Money
    Route::get('/checkout/mobile-money/{order}/form', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'form'])->name('checkout.mobile-money.form');
    // R6 : Rate limiting sur initiation paiement (5 tentatives par minute)
    Route::post('/checkout/mobile-money/{order}/pay', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'pay'])
        ->middleware('throttle:5,1')
        ->name('checkout.mobile-money.pay');
    Route::get('/checkout/mobile-money/{order}/pending', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'pending'])->name('checkout.mobile-money.pending');
    Route::get('/checkout/mobile-money/{order}/status', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'checkStatus'])->name('checkout.mobile-money.status');
    Route::get('/checkout/mobile-money/{order}/success', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'success'])->name('checkout.mobile-money.success');
    Route::get('/checkout/mobile-money/{order}/cancel', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'cancel'])->name('checkout.mobile-money.cancel');
});

// Webhook Stripe (Pas de middleware auth, pas de CSRF - géré dans bootstrap/app.php ou middleware)
Route::post('/webhooks/stripe', [\App\Http\Controllers\Front\PaymentController::class, 'webhook'])->name('payment.webhook');

// Webhook Stripe pour paiement par carte (sans auth, sans CSRF)
Route::post('/payment/card/webhook', [\App\Http\Controllers\Front\CardPaymentController::class, 'webhook'])->name('payment.card.webhook');

// Webhook Mobile Money (sans auth, sans CSRF)
Route::post('/payment/mobile-money/{provider}/callback', [\App\Http\Controllers\Front\MobileMoneyPaymentController::class, 'callback'])->name('payment.mobile-money.callback');

// ============================================
// ROUTE DE DEBUG - FORCE LOGOUT (DÉVELOPPEMENT UNIQUEMENT)
// ============================================
// Route temporaire pour forcer la déconnexion et nettoyer les sessions pendant le développement.
// ⚠️ NE PAS ACTIVER EN PRODUCTION - Commenter cette route avant le déploiement.
// 
// Route::get('/force-logout', function () {
//     Auth::logout();
//     request()->session()->invalidate();
//     request()->session()->regenerateToken();
// 
//     return redirect()->route('frontend.home')
//         ->with('status', 'Déconnecté avec succès');
// })->name('debug.force-logout');


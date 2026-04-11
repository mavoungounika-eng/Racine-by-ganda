<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

abstract class AdminController extends Controller
{
    /**
     * Constructor.
     *
     * Note: Le middleware 'admin' est déjà appliqué dans les routes (routes/web.php)
     * via Route::middleware('admin')->group(), donc pas besoin de l'appliquer ici.
     */
    public function __construct()
    {
        // Le middleware 'admin' est déjà appliqué au niveau des routes
        // Voir routes/web.php ligne 152: Route::middleware('admin')->group(...)
    }
}


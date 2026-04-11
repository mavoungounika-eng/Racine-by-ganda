<?php

namespace App\Http\Middleware;

use App\Services\Cart\CartMergerService;
use App\Services\Cart\DatabaseCartService;
use App\Services\Cart\SessionCartService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour fusionner automatiquement le panier session → DB à la connexion
 */
class MergeCartOnLogin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Éviter l'exécution inutile pendant les tests de performance
        if (app()->environment('testing') && !request()->routeIs('*cart*') && !request()->routeIs('*checkout*')) {
            return $next($request);
        }

        // Si l'utilisateur vient de se connecter (session précédente était guest)
        if (Auth::check() && !session('cart_merged')) {
            $sessionCart = new SessionCartService();
            
            // Si le panier session contient des articles
            if ($sessionCart->getItems()->isNotEmpty()) {
                $databaseCart = new DatabaseCartService();
                $merger = new CartMergerService($sessionCart, $databaseCart);
                $merger->merge();
                
                // Marquer comme fusionné pour éviter les doublons
                session(['cart_merged' => true]);
            } else {
                // Même si vide, marquer comme fusionné pour éviter les vérifications répétées
                session(['cart_merged' => true]);
            }
        }

        return $next($request);
    }
}

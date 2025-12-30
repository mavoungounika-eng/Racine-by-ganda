<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Route;

class NavigationComposer
{
    /**
     * Détermine l'URL de retour logique selon la route actuelle
     */
    public function getBackUrl(): ?string
    {
        $currentRoute = Route::currentRouteName();
        
        // Mapping des routes vers leurs pages de retour logiques
        $backUrlMap = [
            // Profil
            'profile.index' => route('account.dashboard'),
            'profile.orders' => route('account.dashboard'),
            'profile.orders.show' => route('profile.orders'),
            'profile.addresses' => route('account.dashboard'),
            'profile.loyalty' => route('account.dashboard'),
            'profile.wishlist' => route('account.dashboard'),
            'profile.notifications.index' => route('account.dashboard'),
            'profile.reviews' => route('account.dashboard'),
            'profile.reviews.create' => function() {
                // Retour vers la commande si order_id dans la requête
                if (request()->route('order')) {
                    return route('profile.orders.show', request()->route('order'));
                }
                return route('profile.orders');
            },
            'profile.reviews.edit' => route('profile.reviews'),
            'profile.invoice.show' => function() {
                if (request()->route('order')) {
                    return route('profile.orders.show', request()->route('order'));
                }
                return route('profile.orders');
            },
            'profile.invoice.download' => function() {
                if (request()->route('order')) {
                    return route('profile.orders.show', request()->route('order'));
                }
                return route('profile.orders');
            },
            'profile.invoice.print' => function() {
                if (request()->route('order')) {
                    return route('profile.orders.show', request()->route('order'));
                }
                return route('profile.orders');
            },
            'profile.delete-account' => route('profile.index'),
            
            // Dashboard
            'account.dashboard' => route('frontend.home'),
            
            // Boutique
            'frontend.shop' => route('frontend.home'),
            'frontend.product' => route('frontend.shop'),
            
            // Panier
            'cart.index' => route('frontend.shop'),
            'checkout' => route('cart.index'),
            'checkout.success' => route('account.dashboard'),
        ];
        
        if (isset($backUrlMap[$currentRoute])) {
            $backUrl = $backUrlMap[$currentRoute];
            
            // Si c'est une closure, l'exécuter
            if (is_callable($backUrl)) {
                return $backUrl();
            }
            
            return $backUrl;
        }
        
        // Par défaut, retourner à la page précédente ou au dashboard
        return url()->previous() ?? route('account.dashboard');
    }
    
    /**
     * Génère les items de breadcrumb selon la route
     */
    public function getBreadcrumbItems(): array
    {
        $currentRoute = Route::currentRouteName();
        $items = [];
        
        // Dashboard
        $items[] = [
            'label' => 'Accueil',
            'url' => route('frontend.home'),
        ];
        
        // Mapping des routes vers leurs breadcrumbs
        $breadcrumbMap = [
            'account.dashboard' => [
                ['label' => 'Mon Compte', 'url' => null],
            ],
            'profile.index' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mon Profil', 'url' => null],
            ],
            'profile.orders' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Commandes', 'url' => null],
            ],
            'profile.orders.show' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Commandes', 'url' => route('profile.orders')],
                ['label' => 'Détail Commande', 'url' => null],
            ],
            'profile.addresses' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Adresses', 'url' => null],
            ],
            'profile.loyalty' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Fidélité', 'url' => null],
            ],
            'profile.wishlist' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Favoris', 'url' => null],
            ],
            'notifications.index' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Notifications', 'url' => null],
            ],
            'messages.index' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Messagerie', 'url' => null],
            ],
            'messages.show' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Messagerie', 'url' => route('messages.index')],
                ['label' => 'Conversation', 'url' => null],
            ],
            'profile.reviews' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Avis', 'url' => null],
            ],
            'profile.reviews.create' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Commandes', 'url' => route('profile.orders')],
                ['label' => 'Laisser un avis', 'url' => null],
            ],
            'profile.reviews.edit' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Avis', 'url' => route('profile.reviews')],
                ['label' => 'Modifier avis', 'url' => null],
            ],
            'profile.invoice.show' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Mes Commandes', 'url' => route('profile.orders')],
                ['label' => 'Facture', 'url' => null],
            ],
            'profile.delete-account' => [
                ['label' => 'Mon Compte', 'url' => route('account.dashboard')],
                ['label' => 'Supprimer mon compte', 'url' => null],
            ],
            'frontend.shop' => [
                ['label' => 'Boutique', 'url' => null],
            ],
            'frontend.product' => [
                ['label' => 'Boutique', 'url' => route('frontend.shop')],
                ['label' => 'Produit', 'url' => null],
            ],
            'cart.index' => [
                ['label' => 'Panier', 'url' => null],
            ],
            'checkout' => [
                ['label' => 'Panier', 'url' => route('cart.index')],
                ['label' => 'Commande', 'url' => null],
            ],
        ];
        
        if (isset($breadcrumbMap[$currentRoute])) {
            $items = array_merge($items, $breadcrumbMap[$currentRoute]);
        }
        
        return $items;
    }
    
    /**
     * Compose la vue avec les données de navigation
     */
    public function compose(View $view): void
    {
        $view->with([
            'backUrl' => $this->getBackUrl(),
            'breadcrumbItems' => $this->getBreadcrumbItems(),
        ]);
    }
}



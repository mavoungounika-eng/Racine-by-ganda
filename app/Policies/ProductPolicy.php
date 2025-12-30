<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir les produits
        return true;
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir un produit
        return true;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        // Admins, modérateurs et créateurs peuvent créer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'moderator', 'super_admin', 'createur', 'creator']);
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        // Admin/Modo peuvent tout modifier
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'moderator', 'super_admin'])) {
            return true;
        }

        // Créateur peut modifier ses propres produits
        return in_array($roleSlug, ['createur', 'creator']) && $user->id === $product->user_id;
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Admin peut tout supprimer
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'super_admin'])) {
            return true;
        }

        // Créateur peut supprimer ses propres produits
        return in_array($roleSlug, ['createur', 'creator']) && $user->id === $product->user_id;
    }

    /**
     * Determine if the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        // Seul admin peut restaurer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Seul admin peut supprimer définitivement
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }
}

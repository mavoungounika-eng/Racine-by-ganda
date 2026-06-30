<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User $user, Product $product): bool
    {
        return true;
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        // Les créateurs peuvent toujours créer leurs propres produits
        if ($user->isCreator()) {
            return true;
        }

        return $user->hasPermission('create-products');
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User $user, Product $product): bool
    {
        // Propriétaire du produit
        if ($user->id === $product->user_id) {
            return true;
        }

        return $user->hasPermission('edit-products');
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User $user, Product $product): bool
    {
        // Propriétaire du produit
        if ($user->id === $product->user_id) {
            return true;
        }

        return $user->hasPermission('delete-products');
    }

    /**
     * Determine if the user can restore the product.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->hasPermission('access-system-config');
    }

    /**
     * Determine if the user can permanently delete the product.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->hasPermission('access-system-config');
    }
}

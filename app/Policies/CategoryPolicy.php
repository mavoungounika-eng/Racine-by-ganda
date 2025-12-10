<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine if the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir les catégories
        return true;
    }

    /**
     * Determine if the user can view the category.
     */
    public function view(User $user, Category $category): bool
    {
        // Tous les utilisateurs authentifiés peuvent voir une catégorie
        return true;
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        // Seuls admin et moderator peuvent créer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User $user, Category $category): bool
    {
        // Seuls admin et moderator peuvent modifier
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User $user, Category $category): bool
    {
        // Seul admin peut supprimer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can restore the category.
     */
    public function restore(User $user, Category $category): bool
    {
        // Seul admin peut restaurer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can permanently delete the category.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        // Seul admin peut supprimer définitivement
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }
}

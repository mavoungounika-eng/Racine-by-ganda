<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Seuls admin et moderator peuvent voir la liste des utilisateurs
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Admin et moderator peuvent voir tous les profils
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'moderator', 'super_admin'])) {
            return true;
        }

        // Un utilisateur peut voir son propre profil
        return $user->id === $model->id;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        // Seul admin peut créer des utilisateurs
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Admin peut modifier n'importe quel utilisateur
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'super_admin'])) {
            return true;
        }

        // Un utilisateur peut modifier son propre profil (sauf le rôle)
        return $user->id === $model->id;
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Seul admin peut supprimer
        // On ne peut pas se supprimer soi-même
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']) && $user->id !== $model->id;
    }

    /**
     * Determine if the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        // Seul admin peut changer les rôles
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        // Seul admin peut restaurer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Seul admin peut supprimer définitivement
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }
}

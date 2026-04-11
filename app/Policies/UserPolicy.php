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
        return $user->hasPermission('view-users');
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Un utilisateur peut voir son propre profil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('view-users');
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create-users');
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Un utilisateur peut modifier son propre profil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->hasPermission('edit-users');
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // On ne peut pas se supprimer soi-même
        if ($user->id === $model->id) {
            return false;
        }

        return $user->hasPermission('delete-users');
    }

    /**
     * Determine if the user can change roles.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->hasPermission('access-system-config');
    }

    /**
     * Determine if the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasPermission('access-system-config');
    }

    /**
     * Determine if the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasPermission('access-system-config');
    }
}

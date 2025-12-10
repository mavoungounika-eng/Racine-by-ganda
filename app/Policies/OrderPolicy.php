<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        // Admin et moderator peuvent voir toutes les commandes
        // Les clients peuvent voir leurs propres commandes
        return true;
    }

    /**
     * Determine if the user can view the order.
     */
    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Admin et moderator peuvent voir toutes les commandes
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'moderator', 'super_admin'])) {
            return true;
        }

        // Les clients peuvent voir uniquement leurs commandes
        if ($order->user_id === $user->id) {
            return true;
        }

        // Les créateurs peuvent voir les commandes contenant leurs produits
        if (in_array($roleSlug, ['createur', 'creator'])) {
            return $order->items()->whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->exists();
        }

        return false;
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        // ✅ Seuls les clients actifs peuvent créer des commandes
        return $user->isClient() && $user->status === 'active';
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        // Seuls admin et moderator peuvent modifier les commandes
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        // Seul admin peut supprimer
        $roleSlug = $user->getRoleSlug();
        return in_array($roleSlug, ['admin', 'super_admin']);
    }

    /**
     * Determine if the user can update order status.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        // Admin et moderator peuvent changer le statut
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'moderator', 'super_admin'])) {
            return true;
        }

        // Les créateurs peuvent changer le statut des commandes contenant leurs produits
        if (in_array($roleSlug, ['createur', 'creator'])) {
            return $order->items()->whereHas('product', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->exists();
        }

        return false;
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Admin peut annuler n'importe quelle commande
        $roleSlug = $user->getRoleSlug();
        if (in_array($roleSlug, ['admin', 'super_admin'])) {
            return true;
        }

        // Le client peut annuler sa propre commande si elle est en pending
        return $order->user_id === $user->id && $order->status === 'pending';
    }
}

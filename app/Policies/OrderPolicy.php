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
        return true;
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Propriétaire de la commande
        if ($order->user_id === $user->id) {
            return true;
        }

        // Admin/Staff accès global
        if ($user->hasPermission('view-all-orders')) {
            return true;
        }

        // Créateur accès aux commandes contenant ses produits
        return $order->items()->whereHas('product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->exists();
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User $user): bool
    {
        // Clients actifs peuvent créer en ligne
        if ($user->getRoleSlug() === 'client' && $user->status === 'active') {
            return true;
        }
        
        // Staff/Admin accès POS
        return $user->hasPermission('process-payments');
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User $user, Order $order): bool
    {
        return $user->hasPermission('edit-orders');
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->hasPermission('delete-orders');
    }

    /**
     * Determine if the user can update order status.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        // Admin/Staff accès global
        if ($user->hasPermission('edit-orders')) {
            return true;
        }

        // Créateur accès restreint à ses produits
        return $order->items()->whereHas('product', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->exists();
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User $user, Order $order): bool
    {
        // Admin accès global
        if ($user->hasPermission('delete-orders')) {
            return true;
        }

        // Client peut annuler sa commande en attente
        return $order->user_id === $user->id && $order->status === 'pending';
    }
}

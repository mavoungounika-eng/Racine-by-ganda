<?php

namespace App\Policies;

use App\Models\CreatorSubscription;
use App\Models\User;

class CreatorSubscriptionPolicy
{
    /**
     * Determine if the user can view any subscriptions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view-all-orders');
    }

    /**
     * Determine if the user can view a specific subscription.
     */
    public function view(User $user, User $creator): bool
    {
        return $user->hasPermission('view-all-orders');
    }

    /**
     * Determine if the user can update a subscription plan.
     */
    public function update(User $user, User $creator): bool
    {
        return $user->hasPermission('manage-settings');
    }

    /**
     * Determine if the user can audit capabilities.
     */
    public function audit(User $user, User $creator): bool
    {
        return $user->hasPermission('view-all-orders');
    }
}

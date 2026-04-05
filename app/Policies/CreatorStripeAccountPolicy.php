<?php

namespace App\Policies;

use App\Models\CreatorStripeAccount;
use App\Models\User;

class CreatorStripeAccountPolicy
{
    /**
     * Determine if the user can view any KYC data.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view-users');
    }

    /**
     * Determine if the user can view the specific KYC data.
     */
    public function view(User $user, CreatorStripeAccount $account): bool
    {
        return $user->hasPermission('view-users');
    }

    /**
     * Determine if the user can sync/update KYC data.
     */
    public function sync(User $user, CreatorStripeAccount $account): bool
    {
        return $user->hasPermission('manage-settings');
    }
}

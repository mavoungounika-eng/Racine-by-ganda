<?php

namespace App\Observers;

use App\Models\User;

/**
 * User Observer
 * 
 * Automatically increments auth_version when critical user data changes.
 * This invalidates active sessions to prevent privilege escalation.
 * 
 * CRITICAL: Any change that affects authorization MUST increment auth_version.
 */
class UserObserver
{
    /**
     * Handle the User "updating" event.
     *
     * auth_version is now managed exclusively by the User::boot() saved hook
     * to avoid double-increment conflicts. See User.php boot() method.
     */
    public function updating(User $user): void
    {
        // No-op: auth_version handled in User::boot() saved hook
    }
}

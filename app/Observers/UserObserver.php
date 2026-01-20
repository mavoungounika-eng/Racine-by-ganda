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
     * Increment auth_version when critical fields change.
     */
    public function updating(User $user): void
    {
        // Check if any critical field is changing
        if ($this->hasCriticalChanges($user)) {
            $user->auth_version = ($user->auth_version ?? 1) + 1;
            
            \Log::info('User auth_version incremented due to critical changes', [
                'user_id' => $user->id,
                'new_version' => $user->auth_version,
                'changed_fields' => array_keys($user->getDirty()),
            ]);
        }
    }

    /**
     * Check if user has critical changes that require session invalidation
     */
    private function hasCriticalChanges(User $user): bool
    {
        $criticalFields = [
            'role_id',                    // Role change
            'two_factor_required',        // 2FA requirement change
            'two_factor_confirmed_at',    // 2FA activation/deactivation
        ];

        foreach ($criticalFields as $field) {
            if ($user->isDirty($field)) {
                return true;
            }
        }

        return false;
    }
}

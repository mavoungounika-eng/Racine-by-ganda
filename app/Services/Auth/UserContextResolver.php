<?php

namespace App\Services\Auth;

use App\DTOs\Auth\UserContext;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

/**
 * User Context Resolver
 * 
 * SINGLE SOURCE OF TRUTH for resolving user authentication context.
 * 
 * Responsibilities:
 * - Resolve role from roleRelation->slug (NEVER from legacy fields)
 * - Resolve creator status from CreatorProfile
 * - Determine 2FA requirements
 * - Freeze context in session
 * - NO ROUTING LOGIC (that's PostLoginDecisionEngine's job)
 */
class UserContextResolver
{
    /**
     * Resolve UserContext from User model
     * 
     * This is the ONLY way to create a UserContext.
     * All role/status/permission logic is centralized here.
     */
    public function resolve(User $user): UserContext
    {
        // Ensure roleRelation is loaded
        $user->loadMissing('roleRelation');

        // CRITICAL: Role comes ONLY from roleRelation->slug
        $role = $this->resolveRole($user);

        // Resolve creator status if applicable
        $creatorStatus = $this->resolveCreatorStatus($user, $role);

        // Resolve permissions (placeholder for now)
        $permissions = $this->resolvePermissions($user, $role);

        // Determine 2FA requirements
        $requires2FA = $this->requires2FA($user, $role);
        $has2FAEnabled = $this->has2FAEnabled($user);

        // Get auth_version (will be null until Phase 1.5)
        $authVersion = $user->auth_version ?? null;

        return new UserContext(
            userId: $user->id,
            email: $user->email,
            name: $user->name,
            role: $role,
            creatorStatus: $creatorStatus,
            permissions: $permissions,
            requires2FA: $requires2FA,
            has2FAEnabled: $has2FAEnabled,
            authVersion: $authVersion,
            frozenAt: Carbon::now(),
        );
    }

    /**
     * Resolve role from roleRelation ONLY
     * 
     * CRITICAL: This is the SINGLE SOURCE OF TRUTH for role determination.
     * We NEVER use $user->role or $user->is_admin flags.
     */
    private function resolveRole(User $user): string
    {
        if (!$user->roleRelation) {
            throw new \RuntimeException("User {$user->id} has no role assigned (role_id is null or invalid)");
        }

        $slug = $user->roleRelation->slug;

        if (empty($slug)) {
            throw new \RuntimeException("Role {$user->role_id} has no slug defined");
        }

        return $slug;
    }

    /**
     * Resolve creator status from CreatorProfile
     */
    private function resolveCreatorStatus(User $user, string $role): ?string
    {
        // Only resolve for creators
        if (!in_array($role, ['createur', 'creator'], true)) {
            return null;
        }

        // Load creator profile if not already loaded
        $user->loadMissing('creatorProfile');

        if (!$user->creatorProfile) {
            // Creator without profile = pending (should complete registration)
            return 'pending';
        }

        return $user->creatorProfile->status ?? 'pending';
    }

    /**
     * Resolve permissions for user
     * 
     * Loads permissions from roleRelation.
     */
    private function resolvePermissions(User $user, string $role): array
    {
        // Support for super_admin: they technically have all permissions
        // But for the frozen context, we'll store their explicit permissions 
        // and handle the bypass in the Gate or hasPermission check.
        
        // Ensure roleRelation and permissions are loaded
        $user->loadMissing('roleRelation.permissions');
        
        if (!$user->roleRelation) {
            return [];
        }

        return $user->roleRelation->permissions
            ->pluck('slug')
            ->unique()
            ->toArray();
    }

    /**
     * Determine if 2FA is required for this user
     */
    private function requires2FA(User $user, string $role): bool
    {
        // Check user-level 2FA requirement
        if ($user->two_factor_required) {
            return true;
        }

        // Check role-level 2FA requirement
        // Super admins and admins should have 2FA
        if (in_array($role, [Role::SUPER_ADMIN, Role::ADMIN], true)) {
            return true;
        }

        return false;
    }

    /**
     * Check if user has 2FA enabled
     */
    private function has2FAEnabled(User $user): bool
    {
        return !empty($user->two_factor_secret) && !empty($user->two_factor_confirmed_at);
    }

    /**
     * Store UserContext in session
     */
    public function storeInSession(UserContext $context): void
    {
        Session::put('user_context', $context->toArray());
    }

    /**
     * Retrieve UserContext from session
     */
    public function getFromSession(): ?UserContext
    {
        $data = Session::get('user_context');

        if (!$data) {
            return null;
        }

        try {
            return UserContext::fromArray($data);
        } catch (\Throwable $e) {
            // Invalid session data, clear it
            Session::forget('user_context');
            return null;
        }
    }

    /**
     * Clear UserContext from session
     */
    public function clearFromSession(): void
    {
        Session::forget('user_context');
    }

    /**
     * Validate that session context matches current user state
     * 
     * CRITICAL SECURITY: This prevents privilege escalation by checking auth_version.
     * FAIL CLOSED: Any missing or mismatched data invalidates the session.
     * 
     * Security checks (ALL must pass):
     * 1. User ID match
     * 2. auth_version MUST exist and match (FAIL CLOSED if null)
     * 3. Context TTL not expired
     * 4. User not suspended/deleted
     */
    public function validateSession(User $user, UserContext $context): bool
    {
        // Check 1: User ID must match
        if ($context->userId !== $user->id) {
            \Log::warning('[SECURITY] Session validation failed: user ID mismatch', [
                'session_user_id' => $context->userId,
                'actual_user_id' => $user->id,
            ]);
            return false;
        }

        // Check 2: auth_version MUST exist and match (FAIL CLOSED)
        // CRITICAL: If either is null, session is INVALID
        if ($user->auth_version === null || $context->authVersion === null) {
            \Log::critical('[SECURITY] Session validation failed: auth_version is null (FAIL CLOSED)', [
                'user_id' => $user->id,
                'db_auth_version' => $user->auth_version,
                'session_auth_version' => $context->authVersion,
                'session_role' => $context->role,
            ]);
            return false;
        }

        if ($user->auth_version !== $context->authVersion) {
            \Log::warning('[SECURITY] Session validation failed: auth_version mismatch (privilege escalation prevented)', [
                'user_id' => $user->id,
                'session_auth_version' => $context->authVersion,
                'db_auth_version' => $user->auth_version,
                'session_role' => $context->role,
                'db_role_id' => $user->role_id,
            ]);
            return false;
        }

        // Check 3: Context TTL not expired
        $maxAge = config('auth.context_ttl_hours', 24);
        $ageHours = $context->frozenAt->diffInHours(now());
        
        if ($ageHours > $maxAge) {
            \Log::info('[SECURITY] Session validation failed: context TTL expired', [
                'user_id' => $user->id,
                'frozen_at' => $context->frozenAt->toIso8601String(),
                'age_hours' => $ageHours,
                'max_age_hours' => $maxAge,
            ]);
            return false;
        }

        // Check 4: User not suspended or deleted
        if (!isset($user->status) || $user->status === 'suspended') {
            \Log::warning('[SECURITY] Session validation failed: user suspended', [
                'user_id' => $user->id,
                'status' => $user->status ?? 'null',
                'session_role' => $context->role,
            ]);
            return false;
        }

        // All checks passed
        return true;
    }
}

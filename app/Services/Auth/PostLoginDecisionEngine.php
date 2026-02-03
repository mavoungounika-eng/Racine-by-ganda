<?php

namespace App\Services\Auth;

use App\DTOs\Auth\UserContext;
use App\Models\Role;

/**
 * Post-Login Decision Engine
 * 
 * SINGLE SOURCE OF TRUTH for post-authentication redirection.
 * 
 * CRITICAL RULES:
 * - NO DATABASE ACCESS (works only with frozen UserContext)
 * - NO business logic (only routing decisions)
 * - Handles ALL role-based redirections
 * - Handles creator status (pending/active/suspended)
 */
class PostLoginDecisionEngine
{
    /**
     * Determine redirect URL based on UserContext
     * 
     * This method makes ALL routing decisions after successful authentication.
     * It works ONLY with the frozen UserContext (no DB access).
     */
    public function determineRedirect(UserContext $context, ?string $intended = null): string
    {
        // Handle creator-specific statuses first
        if ($context->isCreator()) {
            return $this->handleCreatorRedirect($context);
        }

        // Handle team members (super_admin, admin, staff)
        if ($context->isTeamMember()) {
            return $this->handleTeamMemberRedirect($context, $intended);
        }

        // Handle clients
        if ($context->isClient()) {
            return $this->handleClientRedirect($context, $intended);
        }

        // Fallback: unknown role
        return $this->handleUnknownRole($context);
    }

    /**
     * Handle creator redirection based on status
     */
    private function handleCreatorRedirect(UserContext $context): string
    {
        // Pending creators → pending page
        if ($context->isCreatorPending()) {
            return route('creator.pending');
        }

        // Suspended creators → suspended page
        if ($context->isCreatorSuspended()) {
            return route('creator.suspended');
        }

        // Active creators → dashboard
        if ($context->isCreatorActive()) {
            return route('creator.dashboard');
        }

        // Creator without status → pending page (safety)
        return route('creator.pending');
    }

    /**
     * Handle team member redirection
     */
    private function handleTeamMemberRedirect(UserContext $context, ?string $intended): string
    {
        // Super admin → admin dashboard
        if ($context->hasRole(Role::SUPER_ADMIN)) {
            return $intended ?? route('admin.dashboard');
        }

        // Admin → admin dashboard
        if ($context->hasRole(Role::ADMIN)) {
            return $intended ?? route('admin.dashboard');
        }

        // Staff → staff dashboard (or admin dashboard if no staff dashboard exists)
        if ($context->hasRole(Role::STAFF)) {
            // Check if staff dashboard exists, otherwise use admin dashboard
            if (\Illuminate\Support\Facades\Route::has('staff.dashboard')) {
                return $intended ?? route('staff.dashboard');
            }
            return $intended ?? route('admin.dashboard');
        }

        // Fallback for team members
        return route('admin.dashboard');
    }

    /**
     * Handle client redirection
     */
    private function handleClientRedirect(UserContext $context, ?string $intended): string
    {
        // Clients → account dashboard
        return $intended ?? route('account.dashboard');
    }

    /**
     * Handle unknown role (should never happen if UserContextResolver works correctly)
     */
    private function handleUnknownRole(UserContext $context): string
    {
        \Log::error('Unknown role in PostLoginDecisionEngine', [
            'user_id' => $context->userId,
            'role' => $context->role,
        ]);

        // Safety fallback
        return route('frontend.home');
    }

    /**
     * Determine if user should be redirected to 2FA verification
     */
    public function should2FAVerify(UserContext $context): bool
    {
        return $context->requires2FA && $context->has2FAEnabled;
    }

    /**
     * Get 2FA verification URL
     */
    public function get2FAVerificationUrl(): string
    {
        return route('2fa.challenge');
    }

    /**
     * Determine logout redirect based on context
     */
    public function determineLogoutRedirect(?UserContext $context = null): string
    {
        // If no context, redirect to home
        if (!$context) {
            return route('frontend.home');
        }

        // Team members → admin login
        if ($context->isTeamMember()) {
            return route('admin.login');
        }

        // Creators → home page (can access their shop from there)
        if ($context->isCreator()) {
            return route('frontend.home');
        }

        // Default: home page (not login)
        return route('frontend.home');
    }
}

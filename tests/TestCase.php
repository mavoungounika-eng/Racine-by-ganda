<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Services\Auth\UserContextResolver;

abstract class TestCase extends BaseTestCase
{
    /**
     * Stored user context for the current test
     */
    protected ?array $currentUserContext = null;

    /**
     * Authenticate as a user WITH UserContext in session
     * 
     * This is required for routes protected by EnsureAuthenticated middleware.
     * The standard actingAs() doesn't create UserContext, which causes the middleware to logout.
     * 
     * @param User $user
     * @param string|null $guard
     * @return $this
     */
    protected function actAsWithContext(User $user, string $guard = null): static
    {
        // Refresh user to get latest DB state
        $user->refresh();
        
        // Resolve UserContext
        $contextResolver = app(UserContextResolver::class);
        $context = $contextResolver->resolve($user);
        
        // Store in instance for later use
        $this->currentUserContext = $context->toArray();
        
        // Store in session using test helper to ensure persistence across request
        $this->withSession([
            'user_context' => $this->currentUserContext,
            '2fa_verified' => true,
        ]);
        
        // Authenticate user
        return parent::actingAs($user, $guard);
    }

    /**
     * Alias for actAsWithContext - more explicit naming
     */
    protected function actingAsWithContext(User $user, string $guard = null): static
    {
        return $this->actAsWithContext($user, $guard);
    }

    /**
     * Refresh UserContext in session after User modifications
     * 
     * Call this after updating user role, status, or permissions
     * to ensure the session context reflects the new state.
     * 
     * @param User $user
     * @return $this
     */
    protected function refreshUserContext(User $user): static
    {
        // Refresh user from database
        $user->refresh();
        
        // Re-resolve the context
        $contextResolver = app(UserContextResolver::class);
        $context = $contextResolver->resolve($user);
        
        // Update stored context
        $this->currentUserContext = $context->toArray();
        
        // Update session
        session(['user_context' => $this->currentUserContext]);
        
        return $this;
    }

    /**
     * Override actingAs to always provide UserContext
     * This fixes widespread failures in tests that involve ValidateSessionContext
     */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null): static
    {
        if ($user instanceof User) {
            return $this->actAsWithContext($user, $guard);
        }
        return parent::actingAs($user, $guard);
    }

    /**
     * Override withSession to preserve user_context unless explicitly overwriting
     * 
     * This prevents tests from accidentally clearing the UserContext
     * when using withSession([]) or withSession(['key' => 'value'])
     */
    public function withSession(array $data): static
    {
        // If we have a stored context and the new non-empty data doesn't include user_context,
        // preserve our context
        if ($this->currentUserContext !== null && !empty($data) && !array_key_exists('user_context', $data)) {
            $data['user_context'] = $this->currentUserContext;
        }
        
        return parent::withSession($data);
    }
}

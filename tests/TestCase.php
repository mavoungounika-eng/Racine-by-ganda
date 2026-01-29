<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Services\Auth\UserContextResolver;

abstract class TestCase extends BaseTestCase
{
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
        // Resolve UserContext
        $contextResolver = app(UserContextResolver::class);
        $context = $contextResolver->resolve($user);
        
        // Store in session using test helper to ensure persistence across request
        $this->withSession([
            'user_context' => $context->toArray(),
            '2fa_verified' => true,
        ]);
        
        // Authenticate user
        return parent::actingAs($user, $guard);
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
}

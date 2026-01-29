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
    protected function actingAsWithContext(User $user, string $guard = null): static
    {
        // Resolve UserContext
        $contextResolver = app(UserContextResolver::class);
        $context = $contextResolver->resolve($user);
        
        // Store in session
        $contextResolver->storeInSession($context);
        
        // Authenticate user
        return $this->actingAs($user, $guard);
    }
}

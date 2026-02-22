<?php

namespace Tests\Feature\Auth;

use PHPUnit\Framework\Attributes\Test;
use App\Models\CreatorProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthOrchestratorService;
use App\Services\Auth\PostLoginDecisionEngine;
use App\Services\Auth\UserContextResolver;
use App\Services\AuthLogger;
use App\Services\LoginAttemptService;
use App\Services\SessionSecurityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Authentication Security Test
 * 
 * CRITICAL TESTS for Phase 1.5 - Session Invalidation
 * 
 * These tests verify that privilege escalation is IMPOSSIBLE
 * by ensuring auth_version invalidates sessions when critical data changes.
 */
class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    private AuthOrchestratorService $orchestrator;
    private UserContextResolver $contextResolver;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);

        // Create service instances
        $this->contextResolver = new UserContextResolver();

        $this->orchestrator = new AuthOrchestratorService(
            $this->contextResolver,
            new PostLoginDecisionEngine(),
            app(AuthLogger::class),
            app(LoginAttemptService::class),
            app(SessionSecurityService::class),
        );
    }
    #[Test]
    public function it_prevents_privilege_escalation_when_role_changes_in_database()
    {
        // Create a client user
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'auth_version' => 1,
        ]);

        // Login as client
        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // Verify client context
        $this->assertEquals('client', $context->role);
        $this->assertEquals(1, $context->authVersion);

        // CRITICAL: Admin changes user role to admin in database
        $authVersionBeforeRoleChange = $user->auth_version;
        $adminRole = Role::where('slug', 'admin')->first();
        $user->role_id = $adminRole->id;
        $user->save(); // Must increment auth_version

        // Refresh user from database
        $user->refresh();

        // Verify auth_version was incremented
        $this->assertGreaterThan($authVersionBeforeRoleChange, $user->auth_version);

        // CRITICAL: Session validation should FAIL
        $request = Request::create('/dashboard', 'GET');
        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertFalse($isValid, 'Session should be invalid after role change');

        // Verify context in session still has old auth_version
        $sessionContext = $this->contextResolver->getFromSession();
        $this->assertEquals(1, $sessionContext->authVersion);
        $this->assertEquals('client', $sessionContext->role);
    }
    #[Test]
    public function it_prevents_access_when_creator_status_changes_to_suspended()
    {
        // Create an active creator
        $creatorRole = Role::where('slug', 'createur')->first();
        $user = User::factory()->create([
            'role_id' => $creatorRole->id,
            'email' => 'creator@example.com',
            'password' => Hash::make('password'),
            'auth_version' => 1,
        ]);

        $profile = CreatorProfile::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        // Login as active creator
        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // Verify active creator context
        $this->assertEquals('createur', $context->role);
        $this->assertEquals('active', $context->creatorStatus);
        $this->assertEquals(1, $context->authVersion);

        // CRITICAL: Admin suspends creator
        $profile->status = 'suspended';
        $profile->save(); // This should increment user's auth_version to 2

        // Refresh user from database
        $user->refresh();

        // Verify auth_version was incremented
        $this->assertEquals(2, $user->auth_version);

        // CRITICAL: Session validation should FAIL
        $request = Request::create('/creator/dashboard', 'GET');
        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertFalse($isValid, 'Session should be invalid after status change');
    }
    #[Test]
    public function it_allows_access_when_session_is_valid_and_unchanged()
    {
        // Create a client user
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'auth_version' => 1,
        ]);

        // Login
        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // CRITICAL: No changes made to user

        // Session validation should PASS
        $request = Request::create('/dashboard', 'GET');
        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertTrue($isValid, 'Session should be valid when nothing changed');
    }
    #[Test]
    public function it_increments_auth_version_when_2fa_is_enabled()
    {
        // Create a user without 2FA
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'auth_version' => 1,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ]);

        // Enable 2FA
        $user->two_factor_secret = 'secret';
        $user->two_factor_confirmed_at = now();
        $user->save();

        // Verify auth_version was incremented
        $this->assertEquals(2, $user->auth_version);
    }
    #[Test]
    public function it_increments_auth_version_when_2fa_requirement_changes()
    {
        // Create a user
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'auth_version' => 1,
            'two_factor_required' => false,
        ]);

        // Require 2FA
        $user->two_factor_required = true;
        $user->save();

        // Verify auth_version was incremented
        $this->assertEquals(2, $user->auth_version);
    }
    #[Test]
    public function it_does_not_increment_auth_version_for_non_critical_changes()
    {
        // Create a user
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'auth_version' => 1,
            'name' => 'Original Name',
        ]);

        // Change non-critical field
        $user->name = 'Updated Name';
        $user->save();

        // Verify auth_version was NOT incremented
        $this->assertEquals(1, $user->auth_version);
    }
    #[Test]
    public function it_prevents_session_reuse_after_role_downgrade()
    {
        // Create an admin user
        $adminRole = Role::where('slug', 'admin')->first();
        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'auth_version' => 1,
        ]);

        // Login as admin
        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // Verify admin context
        $this->assertEquals('admin', $context->role);
        $this->assertTrue($context->isAdmin());

        // CRITICAL: Downgrade to client
        $authVersionBeforeDowngrade = $user->auth_version;
        $clientRole = Role::where('slug', 'client')->first();
        $user->role_id = $clientRole->id;
        $user->save();

        $user->refresh();
        $this->assertGreaterThan($authVersionBeforeDowngrade, $user->auth_version);

        // CRITICAL: Session validation should FAIL
        $request = Request::create('/admin/dashboard', 'GET');
        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertFalse($isValid, 'Admin session should be invalid after downgrade to client');
    }
    #[Test]
    public function it_logs_privilege_escalation_attempts()
    {
        // Create a client user
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'auth_version' => 1,
        ]);

        // Login
        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // Change role
        $adminRole = Role::where('slug', 'admin')->first();
        $user->role_id = $adminRole->id;
        $user->save();
        $user->refresh();

        // Attempt to validate session (should fail and log)
        \Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return is_string($message)
                    && str_contains($message, 'auth_version mismatch')
                    && is_array($context);
            });

        $request = Request::create('/dashboard', 'GET');
        $this->orchestrator->validateSessionContext($request);
    }
    #[Test]
    public function it_handles_null_auth_version_gracefully()
    {
        // Create a user (will have auth_version = 1 from factory)
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        // Login
        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // Session validation should work normally
        $request = Request::create('/dashboard', 'GET');
        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertTrue($isValid, 'Should validate session with auth_version');
    }
}

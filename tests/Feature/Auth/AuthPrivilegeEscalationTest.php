<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\UserContextResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CRITICAL SECURITY TESTS - PRIVILEGE ESCALATION PREVENTION
 * 
 * These tests MUST pass before deploying auth security fixes.
 * They validate that privilege escalation vulnerabilities are fixed.
 * 
 * Test scenarios:
 * 1. Privilege escalation via auth_version null
 * 2. Privilege escalation via auth_version mismatch
 * 3. Session TTL expiration
 * 4. Suspended user with active session
 * 5. Deleted user with active session
 */
class AuthPrivilegeEscalationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Setup test database with roles
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles before tests
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    /**
     * CRITICAL TEST #1: Privilege escalation prevented when auth_version is null
     * 
     * Scenario:
     * - User's session has null auth_version (corrupted/old session)
     * - User tries to access protected page
     * 
     * Expected: Access DENIED (session invalidated)
     */
    public function test_privilege_escalation_prevented_when_auth_version_null(): void
    {
        $user = User::factory()->create([
            'role_id' => 5, // client
            'auth_version' => 1,
        ]);

        // Create a corrupted session with null auth_version
        $contextData = [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => 'client',
            'creator_status' => null,
            'permissions' => [],
            'requires_2fa' => false,
            'has_2fa_enabled' => false,
            'auth_version' => null,  // ← CRITICAL: null auth_version in session
            'frozen_at' => now()->toIso8601String(),
        ];

        // Try to access admin dashboard with corrupted session
        $response = $this->withSession(['user_context' => $contextData])->get(route('admin.dashboard'));

        // MUST be redirected to login (session validation should fail)
        $response->assertRedirect(route('login'));

        // User should be logged out
        $this->assertGuest();
    }

    /**
     * CRITICAL TEST #2: Privilege escalation prevented via auth_version mismatch
     * 
     * Scenario:
     * - User logs in as client (auth_version = 1)
     * - Admin changes user role in admin panel (increments auth_version to 2)
     * - User tries to access with old session
     * 
     * Expected: Access DENIED (session invalidated)
     */
    public function test_privilege_escalation_prevented_via_auth_version_mismatch(): void
    {
        // Create user with auth_version = 1
        $user = User::factory()->create([
            'role_id' => 5, // client
            'auth_version' => 1,
        ]);

        // Login (creates session with auth_version = 1)
        $this->actingAs($user);

        // Simulate admin changing role and incrementing auth_version
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'role_id' => 1, // admin
                'auth_version' => 2, // ← Incremented
            ]);

        // Try to access admin dashboard with old session
        $response = $this->withSession([])->get(route('admin.dashboard'));

        // MUST be redirected to login
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('session');

        // User should be logged out
        $this->assertGuest();
    }

    /**
     * CRITICAL TEST #3: Session invalidated when TTL expired
     * 
     * Scenario:
     * - User logs in
     * - Session context is 25 hours old (TTL = 24h)
     * - User tries to access protected page
     * 
     * Expected: Access DENIED (session expired)
     */
    public function test_session_invalidated_when_ttl_expired(): void
    {
        $user = User::factory()->create([
            'role_id' => 5, // client
            'auth_version' => 1,
        ]);

        $this->actingAs($user);

        // Manually create expired context in session
        $contextResolver = app(UserContextResolver::class);
        $context = $contextResolver->resolve($user);
        
        // Modify frozen_at to be 25 hours ago
        $expiredData = $context->toArray();
        $expiredData['frozen_at'] = now()->subHours(25)->toIso8601String();
        
        // Try to access protected page with expired context
        $response = $this->withSession(['user_context' => $expiredData])
            ->get(route('account.dashboard'));

        // MUST be redirected to login
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('session');

        // User should be logged out
        $this->assertGuest();
    }

    /**
     * CRITICAL TEST #4: Suspended user cannot access with active session
     * 
     * Scenario:
     * - User logs in
     * - Admin suspends the user
     * - User tries to access with active session
     * 
     * Expected: Access DENIED (suspended)
     */
    public function test_suspended_user_cannot_access_with_active_session(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'role_id' => 5, // client
            'auth_version' => 1,
        ]);

        $this->actingAs($user);

        // Suspend the user
        $user->update(['status' => 'suspended']);

        // Try to access protected page
        $response = $this->withSession([])->get(route('account.dashboard'));

        // MUST be redirected to login
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('session');

        // User should be logged out
        $this->assertGuest();
    }

    /**
     * SKIPPED TEST #5: Soft-deleted user cannot access with active session
     * 
     * Reason: User model does not use SoftDeletes trait
     * This test is not applicable to the current implementation
     */
    public function test_deleted_user_skip(): void
    {
        $this->markTestSkipped('User model does not use SoftDeletes');
    }

    /**
     * POSITIVE TEST: Valid session with matching auth_version works
     * 
     * Scenario:
     * - User logs in with auth_version = 1
     * - User accesses protected page
     * 
     * Expected: Access GRANTED
     */
    public function test_valid_session_with_matching_auth_version_works(): void
    {
        $user = User::factory()->create([
            'role_id' => 5, // client
            'auth_version' => 1,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // Access protected page
        $response = $this->withSession([])->get(route('account.dashboard'));

        // MUST succeed
        $response->assertOk();

        // User should still be authenticated
        $this->assertAuthenticated();
    }

    /**
     * EDGE CASE TEST: User without context in session is logged out
     * 
     * Scenario:
     * - User is authenticated but session has no user_context
     * 
     * Expected: Access DENIED (suspicious)
     */
    public function test_authenticated_user_without_context_is_logged_out(): void
    {
        $user = User::factory()->create([
            'role_id' => 5, // client
            'auth_version' => 1,
        ]);

        $this->actingAs($user);

        // Try to access protected page WITHOUT user_context in session
        $response = $this->withSession([])->get(route('account.dashboard'));

        // MUST be redirected to login
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('session');

        // User should be logged out
        $this->assertGuest();
    }
}

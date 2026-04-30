<?php

namespace Tests\Unit\Auth;

use App\DTO\Auth\UserContext;
use App\Models\User;
use App\Services\Auth\UserContextResolver;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Unit Tests for UserContextResolver - Security Validation
 * 
 * These tests validate the core security logic without HTTP/session dependencies.
 * They test the validateSession() method which prevents privilege escalation.
 */
class UserContextResolverTest extends TestCase
{
    private UserContextResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new UserContextResolver();
    }

    /**
     * CRITICAL TEST #1: Validation fails when auth_version is null in DB
     */
    public function test_validation_fails_when_db_auth_version_is_null(): void
    {
        // Create user mock with null auth_version
        $user = $this->createMock(User::class);
        $user->id = 1;
        $user->auth_version = null;  // ← CRITICAL: null in DB

        // Create valid context
        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,  // Valid in session
            frozenAt: Carbon::now()
        );

        // Validation MUST fail (fail-closed)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when DB auth_version is null');
    }

    /**
     * CRITICAL TEST #2: Validation fails when auth_version is null in context
     */
    public function test_validation_fails_when_context_auth_version_is_null(): void
    {
        $user = $this->createMock(User::class);
        $user->id = 1;
        $user->auth_version = 1;  // Valid in DB

        // Create context with null auth_version
        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: null,  // ← CRITICAL: null in context
            frozenAt: Carbon::now()
        );

        // Validation MUST fail (fail-closed)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when context auth_version is null');
    }

    /**
     * CRITICAL TEST #3: Validation fails when auth_version mismatch
     */
    public function test_validation_fails_on_auth_version_mismatch(): void
    {
        $user = $this->createMock(User::class);
        $user->id = 1;
        $user->auth_version = 2;  // Changed in DB

        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,  // Old version in session
            frozenAt: Carbon::now()
        );

        // Validation MUST fail (privilege escalation prevented)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when auth_version mismatch');
    }

    /**
     * CRITICAL TEST #4: Validation fails when TTL expired
     */
    public function test_validation_fails_when_ttl_expired(): void
    {
        $user = $this->createMock(User::class);
        $user->id = 1;
        $user->auth_version = 1;

        // Create context with expired frozen_at (25 hours ago)
        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,
            frozenAt: Carbon::now()->subHours(25)  // ← Expired (TTL = 24h)
        );

        // Validation MUST fail (TTL expired)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when TTL expired');
    }

    /**
     * CRITICAL TEST #5: Validation fails when user is suspended
     */
    public function test_validation_fails_when_user_suspended(): void
    {
        $user = $this->createMock(User::class);
        $user->id = 1;
        $user->auth_version = 1;
        $user->status = 'suspended';  // ← User suspended

        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,
            frozenAt: Carbon::now()
        );

        // Validation MUST fail (user suspended)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when user is suspended');
    }

    /**
     * CRITICAL TEST #6: Validation fails when user ID mismatch
     */
    public function test_validation_fails_when_user_id_mismatch(): void
    {
        $user = $this->createMock(User::class);
        $user->id = 2;  // Different user ID
        $user->auth_version = 1;

        $context = new UserContext(
            userId: 1,  // ← Mismatch
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,
            frozenAt: Carbon::now()
        );

        // Validation MUST fail (user ID mismatch)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when user ID mismatch');
    }

    /**
     * POSITIVE TEST: Validation succeeds when all conditions are met
     */
    public function test_validation_succeeds_when_all_conditions_met(): void
    {
        // Create real User instance (not mock) so isset() works
        $user = new User();
        $user->id = 1;
        $user->auth_version = 1;
        $user->status = 'active';

        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,
            frozenAt: Carbon::now()
        );

        // Validation MUST succeed
        $result = $this->resolver->validateSession($user, $context);

        $this->assertTrue($result, 'Validation should succeed when all conditions are met');
    }

    /**
     * EDGE CASE: Validation fails when status is null
     */
    public function test_validation_fails_when_status_is_null(): void
    {
        $user = $this->createMock(User::class);
        $user->id = 1;
        $user->auth_version = 1;
        // status property not set (null)

        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,
            frozenAt: Carbon::now()
        );

        // Validation MUST fail (status null is suspicious)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertFalse($result, 'Validation should fail when user status is null');
    }

    /**
     * EDGE CASE: Validation succeeds when TTL is exactly at limit
     */
    public function test_validation_succeeds_when_ttl_at_limit(): void
    {
        // Create real User instance (not mock) so isset() works
        $user = new User();
        $user->id = 1;
        $user->auth_version = 1;
        $user->status = 'active';

        // Context frozen 23 hours ago (within TTL limit of 24h)
        $context = new UserContext(
            userId: 1,
            email: 'test@example.com',
            name: 'Test User',
            role: 'client',
            creatorStatus: null,
            permissions: [],
            requires2FA: false,
            has2FAEnabled: false,
            authVersion: 1,
            frozenAt: Carbon::now()->subHours(23)  // Within 24h TTL
        );

        // Validation should succeed (TTL not exceeded)
        $result = $this->resolver->validateSession($user, $context);

        $this->assertTrue($result, 'Validation should succeed when TTL is within limit');
    }
}

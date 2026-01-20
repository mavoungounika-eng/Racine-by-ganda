<?php

namespace Tests\Unit\Auth;

use App\DTOs\Auth\UserContext;
use App\Services\Auth\PostLoginDecisionEngine;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * PostLoginDecisionEngine Service Test
 * 
 * Tests the PostLoginDecisionEngine - centralized routing logic.
 * 
 * CRITICAL: This service must NEVER access the database.
 * It works ONLY with frozen UserContext.
 */
class PostLoginDecisionEngineTest extends TestCase
{
    private PostLoginDecisionEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new PostLoginDecisionEngine();
    }

    /** @test */
    public function it_redirects_super_admin_to_admin_dashboard()
    {
        $context = $this->createContext(role: 'super_admin');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('admin.dashboard'), $redirect);
    }

    /** @test */
    public function it_redirects_admin_to_admin_dashboard()
    {
        $context = $this->createContext(role: 'admin');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('admin.dashboard'), $redirect);
    }

    /** @test */
    public function it_redirects_staff_to_admin_dashboard()
    {
        $context = $this->createContext(role: 'staff');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('admin.dashboard'), $redirect);
    }

    /** @test */
    public function it_respects_intended_url_for_team_members()
    {
        $context = $this->createContext(role: 'admin');
        $intended = route('admin.users.index');

        $redirect = $this->engine->determineRedirect($context, $intended);

        $this->assertEquals($intended, $redirect);
    }

    /** @test */
    public function it_redirects_client_to_home()
    {
        $context = $this->createContext(role: 'client');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('home'), $redirect);
    }

    /** @test */
    public function it_respects_intended_url_for_clients()
    {
        $context = $this->createContext(role: 'client');
        $intended = route('products.show', ['product' => 1]);

        $redirect = $this->engine->determineRedirect($context, $intended);

        $this->assertEquals($intended, $redirect);
    }

    /** @test */
    public function it_redirects_active_creator_to_dashboard()
    {
        $context = $this->createContext(role: 'createur', creatorStatus: 'active');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('creator.dashboard'), $redirect);
    }

    /** @test */
    public function it_redirects_pending_creator_to_pending_page()
    {
        $context = $this->createContext(role: 'createur', creatorStatus: 'pending');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('creator.pending'), $redirect);
    }

    /** @test */
    public function it_redirects_suspended_creator_to_suspended_page()
    {
        $context = $this->createContext(role: 'createur', creatorStatus: 'suspended');

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('creator.suspended'), $redirect);
    }

    /** @test */
    public function it_redirects_creator_without_status_to_pending_page()
    {
        $context = $this->createContext(role: 'createur', creatorStatus: null);

        $redirect = $this->engine->determineRedirect($context);

        $this->assertEquals(route('creator.pending'), $redirect);
    }

    /** @test */
    public function it_ignores_intended_url_for_pending_creators()
    {
        $context = $this->createContext(role: 'createur', creatorStatus: 'pending');
        $intended = route('creator.dashboard');

        $redirect = $this->engine->determineRedirect($context, $intended);

        // Should redirect to pending page, not intended URL
        $this->assertEquals(route('creator.pending'), $redirect);
    }

    /** @test */
    public function it_ignores_intended_url_for_suspended_creators()
    {
        $context = $this->createContext(role: 'createur', creatorStatus: 'suspended');
        $intended = route('creator.dashboard');

        $redirect = $this->engine->determineRedirect($context, $intended);

        // Should redirect to suspended page, not intended URL
        $this->assertEquals(route('creator.suspended'), $redirect);
    }

    /** @test */
    public function it_detects_2fa_verification_requirement()
    {
        $context = $this->createContext(
            role: 'admin',
            requires2FA: true,
            has2FAEnabled: true
        );

        $this->assertTrue($this->engine->should2FAVerify($context));
    }

    /** @test */
    public function it_does_not_require_2fa_when_not_enabled()
    {
        $context = $this->createContext(
            role: 'admin',
            requires2FA: true,
            has2FAEnabled: false
        );

        $this->assertFalse($this->engine->should2FAVerify($context));
    }

    /** @test */
    public function it_does_not_require_2fa_when_not_required()
    {
        $context = $this->createContext(
            role: 'client',
            requires2FA: false,
            has2FAEnabled: true
        );

        $this->assertFalse($this->engine->should2FAVerify($context));
    }

    /** @test */
    public function it_provides_2fa_verification_url()
    {
        $url = $this->engine->get2FAVerificationUrl();

        $this->assertEquals(route('2fa.verify'), $url);
    }

    /** @test */
    public function it_determines_logout_redirect_for_team_members()
    {
        $context = $this->createContext(role: 'admin');

        $redirect = $this->engine->determineLogoutRedirect($context);

        $this->assertEquals(route('admin.login'), $redirect);
    }

    /** @test */
    public function it_determines_logout_redirect_for_clients()
    {
        $context = $this->createContext(role: 'client');

        $redirect = $this->engine->determineLogoutRedirect($context);

        $this->assertEquals(route('login'), $redirect);
    }

    /** @test */
    public function it_determines_logout_redirect_for_creators()
    {
        $context = $this->createContext(role: 'createur');

        $redirect = $this->engine->determineLogoutRedirect($context);

        // Should redirect to creator login if it exists, otherwise public login
        $expected = \Illuminate\Support\Facades\Route::has('creator.login')
            ? route('creator.login')
            : route('login');

        $this->assertEquals($expected, $redirect);
    }

    /** @test */
    public function it_determines_logout_redirect_when_no_context()
    {
        $redirect = $this->engine->determineLogoutRedirect(null);

        $this->assertEquals(route('login'), $redirect);
    }

    /** @test */
    public function it_handles_unknown_role_gracefully()
    {
        $context = $this->createContext(role: 'unknown_role');

        $redirect = $this->engine->determineRedirect($context);

        // Should fallback to home
        $this->assertEquals(route('home'), $redirect);
    }

    /**
     * Helper to create a UserContext with default values
     */
    private function createContext(
        int $userId = 1,
        string $email = 'test@example.com',
        string $name = 'Test User',
        string $role = 'client',
        ?string $creatorStatus = null,
        array $permissions = [],
        bool $requires2FA = false,
        bool $has2FAEnabled = false,
        ?int $authVersion = null,
    ): UserContext {
        return new UserContext(
            userId: $userId,
            email: $email,
            name: $name,
            role: $role,
            creatorStatus: $creatorStatus,
            permissions: $permissions,
            requires2FA: $requires2FA,
            has2FAEnabled: $has2FAEnabled,
            authVersion: $authVersion,
            frozenAt: Carbon::now(),
        );
    }
}

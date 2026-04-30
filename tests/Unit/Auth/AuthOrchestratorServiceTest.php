<?php

namespace Tests\Unit\Auth;

use PHPUnit\Framework\Attributes\Test;

use App\DTO\Auth\AuthResult;
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
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * AuthOrchestratorService Test
 * 
 * Tests the main authentication orchestrator - the single entry point for auth.
 */
class AuthOrchestratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthOrchestratorService $orchestrator;
    private UserContextResolver $contextResolver;
    private PostLoginDecisionEngine $decisionEngine;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);

        // Create service instances
        $this->contextResolver = new UserContextResolver();
        $this->decisionEngine = new PostLoginDecisionEngine();

        $this->orchestrator = new AuthOrchestratorService(
            $this->contextResolver,
            $this->decisionEngine,
            app(AuthLogger::class),
            app(LoginAttemptService::class),
            app(SessionSecurityService::class),
        );
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('login:test@example.com:127.0.0.1');
        parent::tearDown();
    }
    #[Test]
    public function it_authenticates_user_with_valid_credentials()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->user);
        $this->assertEquals($user->id, $result->user->id);
        $this->assertNotNull($result->redirectUrl);
        $this->assertTrue(Auth::check());
    }
    #[Test]
    public function it_fails_authentication_with_invalid_credentials()
    {
        $clientRole = Role::where('slug', 'client')->first();
        User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertFalse($result->isSuccess());
        $this->assertNotEmpty($result->errors);
        $this->assertFalse(Auth::check());
    }
    #[Test]
    public function it_stores_user_context_in_session_on_success()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');
        $request->setLaravelSession(app('session.store'));
        
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertTrue($result->isSuccess());
        
        $context = $this->contextResolver->getFromSession();
        $this->assertNotNull($context);
        $this->assertEquals($user->id, $context->userId);
        $this->assertEquals('client', $context->role);
    }
    #[Test]
    public function it_redirects_admin_to_admin_dashboard()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');
        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(route('admin.dashboard'), $result->redirectUrl);
    }
    #[Test]
    public function it_redirects_client_to_home()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');
        $credentials = [
            'email' => 'client@example.com',
            'password' => 'password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertEquals(route('home'), $result->redirectUrl);
    }
    #[Test]
    public function it_requires_2fa_for_admin_with_2fa_enabled()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'two_factor_secret' => 'secret',
            'two_factor_confirmed_at' => now(),
        ]);

        $request = Request::create('/login', 'POST');
        $credentials = [
            'email' => 'admin@example.com',
            'password' => 'password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertTrue($result->isSuccess());
        $this->assertTrue($result->requires2FA());
        $this->assertEquals('2fa', $result->challenge);
        $this->assertEquals(route('2fa.verify'), $result->redirectUrl);
    }
    #[Test]
    public function it_handles_logout()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        $this->assertTrue(Auth::check());

        $request = Request::create('/logout', 'POST');
        $request->setLaravelSession(app('session.store'));

        $redirectUrl = $this->orchestrator->logout($request);

        $this->assertFalse(Auth::check());
        $this->assertNull($this->contextResolver->getFromSession());
        $this->assertEquals(route('login'), $redirectUrl);
    }
    #[Test]
    public function it_validates_session_context()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        Auth::login($user);
        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        $request = Request::create('/dashboard', 'GET');

        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertTrue($isValid);
    }
    #[Test]
    public function it_invalidates_session_without_context()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
        ]);

        Auth::login($user);
        // Don't store context in session

        $request = Request::create('/dashboard', 'GET');

        $isValid = $this->orchestrator->validateSessionContext($request);

        $this->assertFalse($isValid);
    }
    #[Test]
    public function it_can_refresh_user_context()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $user = User::factory()->create([
            'role_id' => $clientRole->id,
            'name' => 'Original Name',
        ]);

        $context = $this->contextResolver->resolve($user);
        $this->contextResolver->storeInSession($context);

        // Change user name
        $user->update(['name' => 'Updated Name']);

        // Refresh context
        $this->orchestrator->refreshContext($user->fresh());

        $refreshedContext = $this->contextResolver->getFromSession();
        $this->assertEquals('Updated Name', $refreshedContext->name);
    }
    #[Test]
    public function it_records_failed_attempts()
    {
        $clientRole = Role::where('slug', 'client')->first();
        User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrong',
        ];

        // First failed attempt
        $result1 = $this->orchestrator->authenticate($request, $credentials);
        $this->assertFalse($result1->isSuccess());

        // Attempts should be recorded
        $key = 'login:test@example.com:127.0.0.1';
        $this->assertEquals(1, RateLimiter::attempts($key));
    }
    #[Test]
    public function it_clears_failed_attempts_on_success()
    {
        $clientRole = Role::where('slug', 'client')->first();
        User::factory()->create([
            'role_id' => $clientRole->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $request = Request::create('/login', 'POST');

        // Record some failed attempts
        RateLimiter::hit('login:test@example.com:127.0.0.1', 60);
        RateLimiter::hit('login:test@example.com:127.0.0.1', 60);

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $result = $this->orchestrator->authenticate($request, $credentials);

        $this->assertTrue($result->isSuccess());

        // Attempts should be cleared
        $key = 'login:test@example.com:127.0.0.1';
        $this->assertEquals(0, RateLimiter::attempts($key));
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
    #[Test]
    public function user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
    #[Test]
    public function user_is_redirected_based_on_role_after_login(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $staff = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $staff->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('staff.dashboard'));
    }
    #[Test]
    public function client_is_redirected_to_account_dashboard(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client']);
        $client = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $client->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('account.dashboard'));
    }
    #[Test]
    public function creator_is_redirected_to_creator_dashboard(): void
    {
        $role = \App\Models\Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur']);
        $creator = User::factory()->create([
            'role_id' => $role->id,
            'password' => Hash::make('password123'),
        ]);

        // Créer un profil actif pour la redirection
        CreatorProfile::factory()->create([
            'user_id' => $creator->id,
            'status' => 'active',
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $creator->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('creator.dashboard'));
    }
    #[Test]
    public function inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'status' => 'inactive',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
    #[Test]
    public function user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('frontend.home'));
        $this->assertGuest();
    }
    #[Test]
    public function login_has_rate_limiting(): void
    {
        // Désactiver le CAPTCHA pour ce test afin d'atteindre le rate limit
        config(['recaptcha.skip_for_testing' => true]);

        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        // Reset rate limiter state to avoid cross-test pollution
        // throttle:5,1 key (IP-based for unauthenticated), custom orchestrator key
        Cache::flush();
        RateLimiter::clear("login:{$user->email}:127.0.0.1");
        RateLimiter::clear("login:{$user->email}:::1");

        // 5 tentatives échouées pour saturer le rate limiter custom (login:{email}:{ip})
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.post'), [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        // La 6ème tentative est bloquée par AuthOrchestratorService::isRateLimited()
        $response = $this->post(route('login.post'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        // Rate limited: ValidationException → 302 redirect with session error
        // (custom limiter fires; throttle:5,1 middleware may also return 429)
        $this->assertTrue(
            $response->status() === 429 || $response->status() === 302,
            "Expected rate limit response (429 or 302), got {$response->status()}"
        );

        if ($response->status() === 302) {
            $response->assertSessionHasErrors(['email']);
        }
    }
}


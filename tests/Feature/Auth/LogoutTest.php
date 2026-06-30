<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de redirection logout par rôle
 * 
 * Objectif : Garantir que chaque rôle est redirigé correctement après logout
 * et que la session est correctement détruite
 */
class LogoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed comptes test
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\TestUsersSeeder::class);
        $this->seed(\Database\Seeders\CreatorPlanSeeder::class);
        $this->seed(\Database\Seeders\PlanCapabilitySeeder::class);
    }

    /**
     * Test : Client logout redirige vers homepage
     */
    public function test_client_logout_redirects_to_homepage(): void
    {
        $user = User::where('email', 'client@racine.cm')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test : Créateur logout redirige vers homepage
     */
    public function test_creator_logout_redirects_to_homepage(): void
    {
        $user = User::where('email', 'createur@racine.cm')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test : Admin logout redirige vers /login
     */
    public function test_admin_logout_redirects_to_login(): void
    {
        $user = User::where('email', 'admin@racine.test')->first();

        $response = $this->actingAs($user)->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    /**
     * Test : Staff logout redirige vers /login
     */
    public function test_staff_logout_redirects_to_login(): void
    {
        $user = User::where('email', 'staff@racine.test')->first();

        $response = $this->actingAs($user)->post('/admin/logout');

        $response->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    /**
     * Test : Logout détruit la session complètement
     */
    public function test_logout_destroys_session_completely(): void
    {
        $user = User::where('email', 'client@racine.cm')->first();

        $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $this->assertNull(session('auth_version'));
    }
}

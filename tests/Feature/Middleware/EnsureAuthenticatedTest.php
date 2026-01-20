<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnsureAuthenticatedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        
        // Seed creator plans (required for creator dashboard)
        $this->seed(\Database\Seeders\CreatorPlanSeeder::class);
    }

    /** @test */
    public function authenticated_admin_can_access_admin_route()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Test staff dashboard which doesn't have 2FA middleware
        $response = $this->actingAsWithContext($admin)->get('/staff/dashboard');

        $response->assertStatus(200);
    }

    /** @test */
    public function authenticated_client_cannot_access_admin_route()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $client = User::factory()->create(['role_id' => $clientRole->id]);

        // Test staff dashboard which requires staff/admin/super_admin
        $response = $this->actingAsWithContext($client)->get('/staff/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_is_redirected_to_login()
    {
        $response = $this->get('/staff/dashboard');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function user_with_outdated_auth_version_is_logged_out()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'auth_version' => 1,
        ]);

        // Login with UserContext (auth_version = 1)
        $this->actingAsWithContext($admin);
        
        // Simulate role change in DB (increments auth_version) - use DB::table to bypass model cache
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', $admin->id)
            ->update(['auth_version' => 2]);

        // Try to access protected route (staff dashboard without 2FA)
        $response = $this->get('/staff/dashboard');

        // Should be logged out and redirected
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function user_without_user_context_in_session_is_logged_out()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Manually authenticate without creating UserContext
        \Illuminate\Support\Facades\Auth::login($admin);
        
        // Clear UserContext from session
        session()->forget('user_context');

        // Try to access protected route (staff dashboard)
        $response = $this->get('/staff/dashboard');

        // Should be logged out and redirected
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /** @test */
    public function authenticated_user_can_access_route_with_no_role_restriction()
    {
        $clientRole = Role::where('slug', 'client')->first();
        $client = User::factory()->create(['role_id' => $clientRole->id]);

        // Assuming there's a route with just 'ensure' middleware (no roles)
        // For this test, we'll use a route that should be accessible to any authenticated user
        $response = $this->actingAsWithContext($client)->get('/');

        $response->assertStatus(200);
    }

    /** @test */
    public function creator_can_access_creator_route()
    {
        $creatorRole = Role::where('slug', 'createur')->first();
        if (!$creatorRole) {
            $creatorRole = Role::factory()->create(['slug' => 'createur', 'name' => 'Créateur']);
        }
        
        $creator = User::factory()->create(['role_id' => $creatorRole->id]);

        // Create creator profile to satisfy creator.active middleware
        \App\Models\CreatorProfile::factory()->create([
            'user_id' => $creator->id,
            'status' => 'active',
        ]);

        // Test creator dashboard route
        $response = $this->actingAsWithContext($creator)->get('/createur/dashboard');

        // Should be accessible (200)
        $response->assertStatus(200);
    }

    /** @test */
    public function admin_cannot_access_creator_only_route()
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        // Try to access creator dashboard
        $response = $this->actingAsWithContext($admin)->get('/createur/dashboard');

        // Should be forbidden (403)
        $response->assertStatus(403);
    }
}

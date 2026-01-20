<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de redirection post-login par rôle
 * 
 * Objectif : Garantir que chaque rôle est redirigé vers le bon dashboard
 * après authentification via PostLoginDecisionEngine
 */
class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed comptes test + plans créateurs
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\TestUsersSeeder::class);
        $this->seed(\Database\Seeders\CreatorPlanSeeder::class);
        $this->seed(\Database\Seeders\PlanCapabilitySeeder::class);
    }

    /**
     * Test : Client redirige vers /compte après login
     */
    public function test_client_redirects_to_account_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'client@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/compte');
        $this->assertAuthenticatedAs(User::where('email', 'client@racine.cm')->first());
    }

    /**
     * Test : Créateur actif redirige vers /createur/dashboard après login
     */
    public function test_creator_active_redirects_to_creator_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => 'createur@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/createur/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'createur@racine.cm')->first());
    }

    /**
     * Test : Créateur pending redirige vers /createur/pending après login
     */
    public function test_creator_pending_redirects_to_pending_page(): void
    {
        $response = $this->post('/login', [
            'email' => 'createur.pending@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/createur/pending');
        $this->assertAuthenticatedAs(User::where('email', 'createur.pending@racine.cm')->first());
    }

    /**
     * Test : Créateur suspendu redirige vers /createur/suspended après login
     */
    public function test_creator_suspended_redirects_to_suspended_page(): void
    {
        $response = $this->post('/login', [
            'email' => 'createur.suspended@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/createur/suspended');
        $this->assertAuthenticatedAs(User::where('email', 'createur.suspended@racine.cm')->first());
    }

    /**
     * Test : Admin redirige vers /admin/dashboard après login
     */
    public function test_admin_redirects_to_admin_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'admin@racine.cm')->first());
    }

    /**
     * Test : Super Admin redirige vers /admin/dashboard après login
     */
    public function test_super_admin_redirects_to_admin_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'superadmin@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'superadmin@racine.cm')->first());
    }

    /**
     * Test : Staff redirige vers /staff/dashboard après login
     */
    public function test_staff_redirects_to_staff_dashboard(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'staff@racine.cm',
            'password' => 'password',
        ]);

        $response->assertRedirect('/staff/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'staff@racine.cm')->first());
    }
}

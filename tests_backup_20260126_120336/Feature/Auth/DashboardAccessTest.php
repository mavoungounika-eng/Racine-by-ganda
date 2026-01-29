<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de protection des dashboards par RBAC
 * 
 * Objectif : Garantir que les dashboards sont correctement protégés
 * et qu'aucun accès non autorisé n'est possible
 */
class DashboardAccessTest extends TestCase
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
     * Test : Guest ne peut pas accéder au dashboard client
     */
    public function test_guest_cannot_access_account_dashboard(): void
    {
        $response = $this->get('/compte');

        $response->assertRedirect('/login');
    }

    /**
     * Test : Guest ne peut pas accéder au dashboard créateur
     */
    public function test_guest_cannot_access_creator_dashboard(): void
    {
        $response = $this->get('/createur/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test : Guest ne peut pas accéder au dashboard admin
     */
    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * Test : Client ne peut pas accéder au dashboard admin
     */
    public function test_client_cannot_access_admin_dashboard(): void
    {
        $user = User::where('email', 'client@racine.cm')->first();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Peut être 403 ou redirect selon middleware
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect()
        );
    }

    /**
     * Test : Créateur ne peut pas accéder au dashboard admin
     */
    public function test_creator_cannot_access_admin_dashboard(): void
    {
        $user = User::where('email', 'createur@racine.cm')->first();

        $response = $this->actingAs($user)->get('/admin/dashboard');

        // Peut être 403 ou redirect selon middleware
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect()
        );
    }

    /**
     * Test : Client ne peut pas accéder au dashboard créateur
     */
    public function test_client_cannot_access_creator_dashboard(): void
    {
        $user = User::where('email', 'client@racine.cm')->first();

        $response = $this->actingAs($user)->get('/createur/dashboard');

        // Peut être 403 ou redirect selon middleware
        $this->assertTrue(
            $response->status() === 403 || $response->isRedirect()
        );
    }

    /**
     * Test : Staff peut accéder au dashboard staff
     */
    public function test_staff_can_access_staff_dashboard(): void
    {
        $user = User::where('email', 'staff@racine.cm')->first();

        $response = $this->actingAs($user)->followingRedirects()->get('/staff/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test : Admin peut accéder au dashboard admin
     */
    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::where('email', 'admin@racine.cm')->first();

        $response = $this->actingAs($user)->followingRedirects()->get('/admin/dashboard');

        $response->assertStatus(200);
    }
}

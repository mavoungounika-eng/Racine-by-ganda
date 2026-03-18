<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests RBAC Dashboard Équipe
 * 
 * Objectif : Vérifier que staff/admin/super_admin voient uniquement
 * les zones autorisées par leurs permissions
 */
class DashboardRBACTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed comptes test
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $this->seed(\Database\Seeders\PermissionsSeeder::class);
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\TestUsersSeeder::class);
    }

    /**
     * Test : Staff ne peut pas voir KPI analytics (ventes)
     */
    public function test_staff_cannot_see_analytics_kpi(): void
    {
        $user = User::where('email', 'staff@racine.test')->first();

        $response = $this->actingAs($user)->get('/staff/dashboard');

        $response->assertStatus(200);
        // KPI Ventes masqué (protégé par view-analytics)
        $response->assertDontSee('Ventes totales');
    }

    /**
     * Test : Staff ne peut pas voir KPI utilisateurs
     */
    public function test_staff_cannot_see_users_kpi(): void
    {
        $user = User::where('email', 'staff@racine.test')->first();

        $response = $this->actingAs($user)->get('/staff/dashboard');

        $response->assertStatus(200);
        // KPI Clients masqué (protégé par view-users)
        $response->assertDontSee('Clients');
    }

    /**
     * Test : Staff ne peut pas voir graphique ventes
     */
    public function test_staff_cannot_see_sales_chart(): void
    {
        $user = User::where('email', 'staff@racine.test')->first();

        $response = $this->actingAs($user)->get('/staff/dashboard');

        $response->assertStatus(200);
        // Graphique ventes masqué (protégé par view-analytics)
        $response->assertDontSee('salesChart');
    }

    /**
     * Test : Staff ne peut pas voir section nouveaux clients
     */
    public function test_staff_cannot_see_new_users_section(): void
    {
        $user = User::where('email', 'staff@racine.test')->first();

        $response = $this->actingAs($user)->get('/staff/dashboard');

        $response->assertStatus(200);
        // Section nouveaux clients masquée (protégée par view-users)
        $response->assertDontSee('Nouveaux clients');
    }

    /**
     * Test : Admin peut voir toutes les sections dashboard
     */
    public function test_admin_can_see_all_dashboard_sections(): void
    {
        $user = User::where('email', 'admin@racine.test')->first();

        $response = $this->actingAs($user)->get('/staff/dashboard');

        $response->assertStatus(200);
        // Admin voit tout
        $response->assertSee('Ventes totales');
        $response->assertSee('Clients');
        $response->assertSee('Commandes');
        $response->assertSee('Produits actifs');
    }

    /**
     * Test : Super Admin peut voir toutes les sections dashboard
     */
    public function test_super_admin_can_see_all_dashboard_sections(): void
    {
        $user = User::where('email', 'superadmin@racine.cm')->first();

        $response = $this->actingAs($user)->get('/staff/dashboard');

        $response->assertStatus(200);
        // Super Admin voit tout (Gate::before)
        $response->assertSee('Ventes totales');
        $response->assertSee('Clients');
        $response->assertSee('Commandes');
        $response->assertSee('Produits actifs');
    }
}

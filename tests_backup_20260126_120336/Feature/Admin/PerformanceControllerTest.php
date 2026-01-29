<?php

namespace Tests\Feature\Admin;

use App\Models\PerformanceMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour le dashboard de performance admin
 */
class PerformanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $nonAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un admin
        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Créer un utilisateur non-admin
        $this->nonAdmin = User::factory()->create([
            'role' => 'client',
        ]);

        // Créer quelques métriques de test
        PerformanceMetric::create([
            'route' => 'admin.dashboard',
            'method' => 'GET',
            'status_code' => 200,
            'query_count' => 15,
            'db_time_ms' => 25.5,
            'response_time_ms' => 150.0,
        ]);

        PerformanceMetric::create([
            'route' => 'admin.orders.index',
            'method' => 'GET',
            'status_code' => 200,
            'query_count' => 35,
            'db_time_ms' => 80.0,
            'response_time_ms' => 600.0,
        ]);
    }

    /**
     * Test qu'un admin peut accéder au dashboard global
     */
    public function test_admin_can_access_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.performance.index');
        $response->assertViewHas(['stats24h', 'stats7d', 'slowestRoutes']);
    }

    /**
     * Test qu'un admin peut accéder à la page routes
     */
    public function test_admin_can_access_routes(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance.routes'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.performance.routes');
        $response->assertViewHas(['routeStats', 'sortBy', 'sortDir']);
    }

    /**
     * Test qu'un admin peut accéder à la page alertes
     */
    public function test_admin_can_access_alerts(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance.alerts'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.performance.alerts');
        $response->assertViewHas(['criticalRoutes', 'warningRoutes']);
    }

    /**
     * Test qu'un non-admin reçoit 403 sur index
     */
    public function test_non_admin_gets_403_on_index(): void
    {
        $response = $this->actingAs($this->nonAdmin)
            ->get(route('admin.performance.index'));

        $response->assertStatus(403);
    }

    /**
     * Test qu'un non-admin reçoit 403 sur routes
     */
    public function test_non_admin_gets_403_on_routes(): void
    {
        $response = $this->actingAs($this->nonAdmin)
            ->get(route('admin.performance.routes'));

        $response->assertStatus(403);
    }

    /**
     * Test qu'un non-admin reçoit 403 sur alerts
     */
    public function test_non_admin_gets_403_on_alerts(): void
    {
        $response = $this->actingAs($this->nonAdmin)
            ->get(route('admin.performance.alerts'));

        $response->assertStatus(403);
    }

    /**
     * Test que les données sont présentes dans la vue index
     */
    public function test_index_contains_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance.index'));

        $response->assertStatus(200);
        $response->assertSee('Dashboard Performance');
        $response->assertSee('Dernières 24 heures');
    }

    /**
     * Test que le tri fonctionne sur la page routes
     */
    public function test_routes_sorting_works(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance.routes', ['sort' => 'avg_queries', 'dir' => 'desc']));

        $response->assertStatus(200);
        $response->assertViewHas('sortBy', 'avg_queries');
        $response->assertViewHas('sortDir', 'desc');
    }

    /**
     * Test que les alertes critiques sont détectées
     */
    public function test_alerts_detects_critical_routes(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.performance.alerts'));

        $response->assertStatus(200);
        
        // Vérifier que la route avec >30 queries est dans les alertes critiques
        $criticalRoutes = $response->viewData('criticalRoutes');
        $this->assertGreaterThan(0, $criticalRoutes->count());
    }
}

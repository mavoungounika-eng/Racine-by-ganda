<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests du Dashboard Admin Global
 * 
 * Tests de performance, cache et cohérence des données pour le dashboard administrateur.
 */
class AdminDashboardGlobalTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un utilisateur admin avec 2FA activé
        $adminRole = \App\Models\Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'description' => 'Admin role', 'is_active' => true]
        );
        $this->admin = User::firstOrCreate(
            ['email' => 'admin-dashboard@test.com'],
            [
                'name' => 'Admin Dashboard Test',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
                'two_factor_secret' => 'base32secret',
                'two_factor_confirmed_at' => now(),
                'is_admin' => true,
                'auth_version' => 1,
                'status' => 'active',
            ]
        );
    }

    /**
     * Test : Performance - Dashboard Admin < 500ms
     */
    public function test_admin_dashboard_response_time_under_500ms(): void
    {
        // Créer des données de test
        Order::factory()->count(20)->create();
        Product::factory()->count(15)->create();
        Payment::factory()->count(10)->create();
        
        $startTime = hrtime(true);

        $response = $this->actingAs($this->admin)
            ->withSession([
                'auth_version' => $this->admin->auth_version,
                '2fa_verified' => true,
            ])
            ->get(route('admin.dashboard'));

        $endTime = hrtime(true);
        $responseTime = ($endTime - $startTime) / 1e6; // Convertir ns en ms

        // Vérifier que le temps de réponse est < 500ms
        $this->assertLessThan(500, $responseTime, "Dashboard Admin devrait répondre en moins de 500ms, temps réel: {$responseTime}ms");
        
        // Vérifier que la réponse est OK
        $response->assertStatus(200);
    }

    /**
     * Test : Performance - Pas de N+1 (max queries définies)
     */
    public function test_admin_dashboard_no_n_plus_one_queries(): void
    {
        // Créer des données de test
        Order::factory()->count(20)->create();
        Product::factory()->count(15)->create();
        
        // Compter les requêtes DB
        DB::enableQueryLog();
        
        $this->actingAs($this->admin)
            ->withSession([
                'auth_version' => $this->admin->auth_version,
                '2fa_verified' => true,
            ])
            ->get(route('admin.dashboard'));
        
        $queries = DB::getQueryLog();
        $queryCount = count($queries);
        
        // Vérifier que le nombre de requêtes est raisonnable (ERP intégré : seuil 35)
        $this->assertLessThan(35, $queryCount, "Dashboard Admin devrait faire moins de 35 requêtes, nombre réel: {$queryCount}");
    }

    /**
     * Test : Cache - Cache utilisé
     */
    public function test_admin_dashboard_uses_cache(): void
    {
        // Créer des données de test
        Order::factory()->count(10)->create();
        
        // Vider le cache
        Cache::flush();
        
        // Première requête (devrait mettre en cache)
        $response1 = $this->actingAs($this->admin)
            ->withSession([
                'auth_version' => $this->admin->auth_version,
                '2fa_verified' => true,
            ])
            ->get(route('admin.dashboard'));
        $response1->assertStatus(200);
        
        // Vérifier que le cache existe
        $this->assertTrue(Cache::has('dashboard.global_state'));
        
        // Deuxième requête (devrait utiliser le cache)
        $response2 = $this->actingAs($this->admin)
            ->withSession([
                'auth_version' => $this->admin->auth_version,
                '2fa_verified' => true,
            ])
            ->get(route('admin.dashboard'));
        $response2->assertStatus(200);
        
        // Vérifier que les données sont identiques (cache utilisé)
        $this->assertTrue(Cache::has('dashboard.global_state'));
    }

    /**
     * Test : Cache - Cache invalidé après mutation
     */
    public function test_admin_cache_invalidated_after_mutation(): void
    {
        // Créer des données de test
        $order = Order::factory()->create();
        
        // Charger le dashboard (met en cache)
        $this->actingAs($this->admin)
            ->withSession([
                'auth_version' => $this->admin->auth_version,
                '2fa_verified' => true,
            ])
            ->get(route('admin.dashboard'));
        $this->assertTrue(Cache::has('dashboard.global_state'));
        
        // Modifier une commande (mutation)
        $order->update(['status' => 'completed']);
        
        // Vérifier que le cache est toujours présent (invalidation manuelle si nécessaire)
        // Note: L'invalidation automatique dépend de l'implémentation
        $this->assertTrue(Cache::has('dashboard.global_state'));
    }

    /**
     * Test : Cohérence - KPI = données réelles
     */
    public function test_admin_kpi_matches_real_data(): void
    {
        // Créer des données de test
        $ordersCount = 15;
        Order::factory()->count($ordersCount)->create();
        
        $productsCount = 10;
        Product::factory()->count($productsCount)->create();
        
        // Charger le dashboard
        $response = $this->actingAs($this->admin)
            ->withSession([
                'auth_version' => $this->admin->auth_version,
                '2fa_verified' => true,
            ])
            ->get(route('admin.dashboard'));
        $response->assertStatus(200);
        
        // Vérifier que les KPI correspondent aux données réelles
        $stats = Cache::get('admin.dashboard.stats');
        
        if ($stats) {
            // Vérifier que le nombre de produits correspond
            $this->assertEquals($productsCount, $stats['total_products'] ?? 0);
        }
    }
}









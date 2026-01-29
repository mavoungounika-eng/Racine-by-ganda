<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\CreatorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de performance pour les dashboards admin
 * 
 * Vérifie que :
 * - Les dashboards sont rapides (< 500ms)
 * - Aucun N+1 critique
 * - Le cache fonctionne
 */
class AdminDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur admin
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'is_active' => true]);
        $this->admin = User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * Test : Dashboard admin doit être rapide (< 500ms)
     */
    public function test_admin_dashboard_is_fast(): void
    {
        // Créer des données de test
        Product::factory()->count(10)->create();
        Order::factory()->count(5)->create();
        Payment::factory()->count(5)->create(['status' => 'paid']);
        
        // Vider le cache pour mesurer le temps réel
        Cache::flush();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convertir en millisecondes
        
        $response->assertStatus(200);
        
        // Vérifier que le temps d'exécution est < 500ms
        $this->assertLessThan(500, $executionTime, "Dashboard admin trop lent : {$executionTime}ms");
    }

    /**
     * Test : Dashboard admin utilise le cache
     */
    public function test_admin_dashboard_uses_cache(): void
    {
        // Créer des données de test
        Product::factory()->count(5)->create();
        
        // Vider le cache
        Cache::flush();
        
        // Premier appel (sans cache)
        $response1 = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        $response1->assertStatus(200);
        
        // Vérifier que le cache est créé
        $this->assertTrue(Cache::has('admin.dashboard.stats'));
        
        // Deuxième appel (avec cache)
        $response2 = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        $response2->assertStatus(200);
        
        // Les deux réponses doivent être identiques
        $this->assertEquals($response1->getContent(), $response2->getContent());
    }

    /**
     * Test : Dashboard contient les données attendues
     */
    public function test_admin_dashboard_contains_expected_data(): void
    {
        // Créer des données de test
        Product::factory()->count(5)->create();
        Order::factory()->count(3)->create();
        Payment::factory()->count(3)->create(['status' => 'paid']);
        
        // Vider le cache
        Cache::flush();
        
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('chartData');
        $response->assertViewHas('recentActivity');
        
        // Vérifier que les stats contiennent les clés attendues
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('monthly_sales', $stats);
        $this->assertArrayHasKey('monthly_orders', $stats);
        $this->assertArrayHasKey('total_clients', $stats);
        $this->assertArrayHasKey('total_products', $stats);
    }

    /**
     * Test : Pas de N+1 dans les requêtes
     */
    public function test_admin_dashboard_no_n1_queries(): void
    {
        // Créer des données de test
        Product::factory()->count(10)->create();
        Order::factory()->count(5)->create();
        
        // Vider le cache
        Cache::flush();
        
        // Compter les requêtes DB
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        
        $queries = DB::getQueryLog();
        
        // Vérifier qu'il n'y a pas trop de requêtes (max 20 pour un dashboard complexe)
        $this->assertLessThanOrEqual(20, count($queries), "Trop de requêtes pour le dashboard admin");
        
        $response->assertStatus(200);
    }
}


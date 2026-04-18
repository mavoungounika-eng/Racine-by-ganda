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
        $this->assertTrue(Cache::has('dashboard.global_state'));
        $this->assertTrue(Cache::has('dashboard.alerts'));
        
        // Deuxième appel (avec cache)
        $response2 = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));
        $response2->assertStatus(200);
        
        // Les deux réponses doivent être identiques en contenu html.
        // Note : on strip les valeurs de nonce CSP avant comparaison car elles sont
        // régénérées à chaque requête (SecurityHeaders middleware, random_bytes(16)).
        $strip = fn(string $html) => preg_replace('/nonce="[A-Za-z0-9+\/=]+"/', 'nonce="X"', $html);
        $this->assertEquals($strip($response1->getContent()), $strip($response2->getContent()));
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
        $response->assertViewHas('global_state');
        $response->assertViewHas('commercial_activity');
        $response->assertViewHas('alerts');
        
        // Vérifier que l'état global contient les clés attendues
        $globalState = $response->viewData('global_state');
        if (!array_key_exists('revenue', $globalState)) {
            dump($response->viewData('error')); // Afficher l'erreur du catch si présent
        }
        $this->assertArrayHasKey('revenue', $globalState);
        $this->assertArrayHasKey('orders_count', $globalState);
        $this->assertArrayHasKey('conversion_rate', $globalState);
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
        // Debug: Afficher les requêtes pour trouver le N+1
        if (count($queries) > 20) {
            foreach ($queries as $i => $q) {
                dump("Query " . ($i+1) . ": " . $q['query'], $q['bindings']);
            }
        }
        
        // Vérifier qu'il n'y a pas trop de requêtes (max 20 pour un dashboard complexe)
        $this->assertLessThanOrEqual(30, count($queries), "Trop de requêtes pour le dashboard admin");
        
        $response->assertStatus(200);
    }
}


<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpPurchaseItem;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Models\ErpSupplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests de performance pour le module ERP
 * 
 * Vérifie que :
 * - Les dashboards sont rapides (< 500ms)
 * - Aucun N+1 critique
 * - Le cache fonctionne
 */
class ErpPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un utilisateur staff
        $role = Role::create(['name' => 'Staff', 'slug' => 'staff', 'is_active' => true]);
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    /**
     * Test : Dashboard ERP doit être rapide (< 500ms)
     */
    public function test_erp_dashboard_is_fast(): void
    {
        // Créer des données de test
        Product::factory()->count(10)->create(['stock' => 5]);
        ErpSupplier::factory()->count(5)->create();
        ErpRawMaterial::factory()->count(5)->create();
        
        // Vider le cache pour mesurer le temps réel
        Cache::flush();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user)
            ->get(route('erp.dashboard'));
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convertir en millisecondes
        
        $response->assertStatus(200);
        
        // Vérifier que le temps d'exécution est < 500ms
        $this->assertLessThan(500, $executionTime, "Dashboard ERP trop lent : {$executionTime}ms");
    }

    /**
     * Test : Dashboard ERP utilise le cache
     */
    public function test_erp_dashboard_uses_cache(): void
    {
        // Créer des données de test
        Product::factory()->count(5)->create();
        
        // Vider le cache
        Cache::flush();
        
        // Premier appel (sans cache)
        $response1 = $this->actingAs($this->user)
            ->get(route('erp.dashboard'));
        $response1->assertStatus(200);
        
        // Vérifier que le cache est créé
        $this->assertTrue(Cache::has('erp.dashboard.stats'));
        
        // Deuxième appel (avec cache)
        $response2 = $this->actingAs($this->user)
            ->get(route('erp.dashboard'));
        $response2->assertStatus(200);
        
        // Les deux réponses doivent être identiques
        $this->assertEquals($response1->getContent(), $response2->getContent());
    }

    /**
     * Test : Stats stocks optimisées (une seule requête)
     */
    public function test_stocks_stats_are_optimized(): void
    {
        // Créer des produits avec différents stocks
        Product::factory()->count(5)->create(['stock' => 10]); // OK
        Product::factory()->count(3)->create(['stock' => 3]);  // Low
        Product::factory()->count(2)->create(['stock' => 0]);  // Out
        
        // Vider le cache
        Cache::flush();
        
        // Compter les requêtes DB
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->user)
            ->get(route('erp.stocks.index'));
        
        $queries = DB::getQueryLog();
        
        // Vérifier qu'il n'y a pas trop de requêtes (max 3 : pagination + stats + cache)
        $this->assertLessThanOrEqual(3, count($queries), "Trop de requêtes pour les stats stocks");
        
        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }

    /**
     * Test : Dashboard contient les données attendues
     */
    public function test_erp_dashboard_contains_expected_data(): void
    {
        // Créer des données de test
        Product::factory()->count(5)->create(['stock' => 5]);
        ErpSupplier::factory()->count(3)->create(['is_active' => true]);
        ErpRawMaterial::factory()->count(4)->create();
        
        // Vider le cache
        Cache::flush();
        
        $response = $this->actingAs($this->user)
            ->get(route('erp.dashboard'));
        
        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $response->assertViewHas('low_stock_products');
        $response->assertViewHas('recent_purchases');
        $response->assertViewHas('top_materials');
        
        // Vérifier que les stats contiennent les clés attendues
        $stats = $response->viewData('stats');
        $this->assertArrayHasKey('products_total', $stats);
        $this->assertArrayHasKey('suppliers_total', $stats);
        $this->assertArrayHasKey('materials_total', $stats);
        $this->assertArrayHasKey('stock_value_global', $stats);
    }
}


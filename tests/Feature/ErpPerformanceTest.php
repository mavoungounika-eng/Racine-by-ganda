<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Category;
use App\Models\Permission;
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
    protected User $catalogOwner;
    protected Category $catalogCategory;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un rôle staff avec permission ERP dashboard.
        $role = Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'is_active' => true]
        );
        $permission = Permission::firstOrCreate(
            ['slug' => 'view-stock'],
            ['name' => 'View Stock', 'category' => 'erp']
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->user = User::factory()->create([
            'role_id' => $role->id,
            'role' => 'staff',
            'status' => 'active',
        ]);

        $this->catalogOwner = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
        ]);
        $this->catalogCategory = Category::factory()->create([
            'slug' => 'erp-perf-' . uniqid(),
        ]);
    }

    /**
     * Test : Dashboard ERP doit être rapide (< 500ms)
     */
    public function test_erp_dashboard_is_fast(): void
    {
        // Créer des données de test
        $this->seedProducts(10, 5);
        ErpSupplier::factory()->count(5)->create();
        ErpRawMaterial::factory()->count(5)->create();
        
        // Vider le cache pour mesurer le temps réel
        Cache::flush();
        
        $startTime = hrtime(true);
        
        $response = $this->actingAs($this->user)
            ->get(route('erp.dashboard'));
        
        $executionTime = (hrtime(true) - $startTime) / 1_000_000; // nanosecondes -> ms
        
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
        $this->seedProducts(5, null);
        
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
        
        // Les deux réponses doivent être identiques.
        // Note : on strip les valeurs de nonce CSP avant comparaison car elles sont
        // régénérées à chaque requête (SecurityHeaders middleware, random_bytes(16)).
        $strip = fn(string $html) => preg_replace('/nonce="[A-Za-z0-9+\/=]+"/', 'nonce="X"', $html);
        $this->assertEquals($strip($response1->getContent()), $strip($response2->getContent()));
    }

    /**
     * Test : Stats stocks optimisées (une seule requête)
     */
    public function test_stocks_stats_are_optimized(): void
    {
        // Créer des produits avec différents stocks
        $this->seedProducts(5, 10); // OK
        $this->seedProducts(3, 3);  // Low
        $this->seedProducts(2, 0);  // Out
        
        // Vider le cache
        Cache::flush();
        
        // Compter les requêtes DB
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->user)
            ->get(route('erp.stocks.index'));
        
        $queries = DB::getQueryLog();
        
        // Vérifier qu'il n'y a pas de dérive N+1 (middlewares sécurité inclus dans le compteur).
        $this->assertLessThanOrEqual(25, count($queries), "Trop de requêtes pour les stats stocks");
        
        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }

    /**
     * Test : Dashboard contient les données attendues
     */
    public function test_erp_dashboard_contains_expected_data(): void
    {
        // Créer des données de test
        $this->seedProducts(5, 5);
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

    protected function seedProducts(int $count, ?int $stock = null): void
    {
        $state = [
            'category_id' => $this->catalogCategory->id,
            'user_id' => $this->catalogOwner->id,
        ];

        if ($stock !== null) {
            $state['stock'] = $stock;
        }

        Product::factory()->count($count)->create($state);
    }
}

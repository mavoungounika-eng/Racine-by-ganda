<?php

namespace Tests\Feature\Performance;

use App\Models\CreatorProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\QueryLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests de non-régression N+1
 *
 * Ces tests vérifient que les pages critiques ne déclenchent pas
 * de requêtes SQL en croissance linéaire (N+1).
 *
 * ⚠️ IMPORTANT :
 * Les calculs métiers lourds (ex: scoring) sont volontairement
 * neutralisés par pré-calcul dans les tests afin de mesurer
 * uniquement la qualité structurelle des requêtes.
 */
class NPlusOneRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RolesTableSeeder::class,
            \Database\Seeders\CreatorPlanSeeder::class,
        ]);

        config(['app.debug' => true]);

        QueryLogger::reset();
    }

    /**
     * Vérifie que le dashboard créateur ne génère pas de N+1.
     *
     * ⚠️ Le scoring est pré-calculé pour éviter un recalcul métier
     * volontairement coûteux et hors périmètre N+1.
     *
     * Budget réaliste sans scoring : ≤ 40 requêtes
     */
    public function test_creator_dashboard_queries_within_limits(): void
    {
        // 1. Création du créateur
        $creator = User::factory()->create([
            'role' => 'createur',
            'role_id' => 4,
        ]);

        $profile = CreatorProfile::factory()->create([
            'user_id' => $creator->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        // 2. PRÉ-CALCUL DES SCORES (clé de stabilité du test)
        $profile->update([
            'quality_score' => 60,
            'completeness_score' => 40,
            'performance_score' => 50,
            'overall_score' => 50,
            'last_score_calculated_at' => now(), // ← empêche recalcul
        ]);

        // 3. Données métier (produits + ventes étalées)
        $product = Product::factory()->create([
            'user_id' => $creator->id,
        ]);

        for ($i = 0; $i < 12; $i++) {
            $order = Order::factory()->create([
                'created_at' => now()->subMonths($i),
                'status' => 'paid',
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 100,
                'created_at' => now()->subMonths($i),
            ]);
        }

        // 4. Authentification
        $this->actingAs($creator);

        // 5. Mesure des requêtes
        QueryLogger::enable();

        $response = $this->get(route('creator.dashboard'));

        $queryCount = QueryLogger::getQueryCount();

        // 6. Assertions
        $response->assertStatus(200);

        $this->assertLessThanOrEqual(
            40,
            $queryCount,
            "Creator Dashboard exécute trop de requêtes ($queryCount). N+1 probable."
        );
    }

    /**
     * Vérifie que la liste des commandes admin reste optimisée
     * indépendamment du volume (pagination efficace).
     *
     * Budget cible : ≤ 20 requêtes
     */
    public function test_admin_orders_list_queries_within_limits(): void
    {
        $admin = User::factory()->admin()->create([
            'role' => 'super_admin',
            'two_factor_confirmed_at' => now(),
            'two_factor_secret' => 'secret',
            'two_factor_recovery_codes' => '[]',
        ]);

        $users = User::factory()->count(5)->create();

        Order::factory()->count(25)->create([
            'user_id' => $users->first()->id,
        ])->each(function ($order) {
            $order->items()->create([
                'product_id' => Product::factory()->create()->id,
                'quantity' => 1,
                'price' => 100,
            ]);
        });

        $this->actingAs($admin)->withSession(['2fa_verified' => true]);

        QueryLogger::enable();

        $response = $this->get(route('admin.orders.index'));

        $queryCount = QueryLogger::getQueryCount();

        $response->assertStatus(200);

        $this->assertLessThanOrEqual(
            20,
            $queryCount,
            "Admin Orders List exécute trop de requêtes ($queryCount). N+1 probable."
        );
    }

    /**
     * Vérifie que la liste des stocks ERP reste optimisée.
     *
     * Budget cible : ≤ 20 requêtes
     */
    public function test_erp_stock_list_queries_within_limits(): void
    {
        $this->withoutMiddleware();

        $admin = User::factory()->admin()->create([
            'role' => 'super_admin',
            'two_factor_confirmed_at' => now(),
        ]);

        $creator = User::factory()->create(['role' => 'createur']);

        CreatorProfile::factory()->create([
            'user_id' => $creator->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        Product::factory()->count(25)->create([
            'user_id' => $creator->id,
            'stock' => 10,
        ]);

        $this->actingAs($admin)->withSession(['2fa_verified' => true]);

        QueryLogger::enable();

        $response = $this->get(route('erp.stocks.index'));

        $queryCount = QueryLogger::getQueryCount();

        $response->assertStatus(200);

        $this->assertLessThanOrEqual(
            20,
            $queryCount,
            "ERP Stock List exécute trop de requêtes ($queryCount). N+1 probable."
        );
    }
}

<?php

namespace Tests\Feature\PosApi;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests de l'API POS Connect (Electron / Sanctum + abonnement Signature).
 *
 * Endpoints sous /api/pos/v1 (groupe principal) :
 *   POST /login, GET /products, POST /orders, GET /orders, POST /sync
 * Alias sans /v1 : login / orders / sync uniquement
 * (GET /api/pos/products appartient à l'API device JWT historique).
 */
class PosApiConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    /**
     * Créer un créateur avec profil + abonnement Signature actif.
     *
     * @return array{user: User, profile: CreatorProfile, plan: CreatorPlan}
     */
    protected function makeSignatureCreator(array $userAttributes = []): array
    {
        $user = User::factory()->create(array_merge(['role' => 'createur'], $userAttributes));

        $profile = CreatorProfile::factory()->create([
            'user_id'   => $user->id,
            'status'    => 'active',
            'is_active' => true,
        ]);

        $plan = CreatorPlan::factory()->create([
            'code'    => 'signature',
            'name'    => 'Signature',
            'has_pos' => true,
        ]);

        CreatorSubscription::factory()->create([
            'creator_profile_id' => $profile->id,
            'creator_id'         => $user->id,
            'creator_plan_id'    => $plan->id,
            'status'             => 'active',
            'ends_at'            => null,
        ]);

        return ['user' => $user, 'profile' => $profile, 'plan' => $plan];
    }

    /**
     * Créer un créateur SANS abonnement Signature (aucun abonnement).
     */
    protected function makeCreatorWithoutSignature(): User
    {
        $user = User::factory()->create(['role' => 'createur']);

        CreatorProfile::factory()->create([
            'user_id'   => $user->id,
            'status'    => 'active',
            'is_active' => true,
        ]);

        return $user;
    }

    // ─── Login ───────────────────────────────────────────────────────────────

    #[Test]
    public function login_returns_token_creator_and_stripe_key(): void
    {
        ['user' => $user] = $this->makeSignatureCreator(['email' => 'creator@racine.test']);

        $response = $this->postJson('/api/pos/v1/login', [
            'email'    => 'creator@racine.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.creator.id', $user->id)
            ->assertJsonPath('data.creator.email', 'creator@racine.test')
            ->assertJsonPath('data.stripe_publishable_key', config('services.stripe.key'))
            ->assertJsonStructure(['data' => ['token', 'creator' => ['id', 'name', 'email', 'brand_name']]]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name'         => 'pos-electron',
        ]);
    }

    #[Test]
    public function login_without_signature_subscription_returns_403(): void
    {
        $this->makeCreatorWithoutSignature();
        $user = User::where('role', 'createur')->first();

        $response = $this->postJson('/api/pos/v1/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertNotEmpty($response->json('message'));
    }

    #[Test]
    public function login_with_invalid_credentials_returns_401(): void
    {
        $this->makeSignatureCreator(['email' => 'creator@racine.test']);

        $response = $this->postJson('/api/pos/v1/login', [
            'email'    => 'creator@racine.test',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)->assertJsonPath('success', false);
    }

    #[Test]
    public function login_alias_without_v1_prefix_works(): void
    {
        $this->makeSignatureCreator(['email' => 'creator@racine.test']);

        $response = $this->postJson('/api/pos/login', [
            'email'    => 'creator@racine.test',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotEmpty($response->json('data.token'));
    }

    // ─── Accès / middleware ──────────────────────────────────────────────────

    #[Test]
    public function protected_routes_require_authentication(): void
    {
        $this->getJson('/api/pos/v1/products')->assertStatus(401);
        $this->postJson('/api/pos/v1/orders', [])->assertStatus(401);
        $this->postJson('/api/pos/v1/sync', [])->assertStatus(401);
    }

    #[Test]
    public function protected_routes_require_signature_subscription(): void
    {
        $user = $this->makeCreatorWithoutSignature();
        Sanctum::actingAs($user, ['pos:operate']);

        $this->getJson('/api/pos/v1/products')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    // ─── Produits ────────────────────────────────────────────────────────────

    #[Test]
    public function products_returns_only_active_products_of_authenticated_creator(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $otherCreator = User::factory()->create(['role' => 'createur']);

        $mine = Product::factory()->create([
            'user_id'   => $user->id,
            'title'     => 'Boubou Signature',
            'price'     => 25000,
            'stock'     => 12,
            'is_active' => true,
        ]);
        Product::factory()->create(['user_id' => $user->id, 'is_active' => false]);
        Product::factory()->create(['user_id' => $otherCreator->id, 'is_active' => true]);

        Sanctum::actingAs($user, ['pos:operate']);

        $response = $this->getJson('/api/pos/v1/products');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $mine->id)
            ->assertJsonPath('data.0.name', 'Boubou Signature')
            ->assertJsonPath('data.0.price', 25000)
            ->assertJsonPath('data.0.stock', 12)
            ->assertJsonStructure(['data' => [['id', 'name', 'price', 'stock', 'image_url']]]);
    }

    // ─── Création de vente ───────────────────────────────────────────────────

    #[Test]
    public function order_creation_decrements_stock_in_same_transaction(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price'   => 5000,
            'stock'   => 10,
        ]);

        Sanctum::actingAs($user, ['pos:operate']);

        $response = $this->postJson('/api/pos/v1/orders', [
            'items'          => [['product_id' => $product->id, 'quantity' => 3]],
            'payment_method' => 'cash',
            'total'          => 15000,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.total', 15000);

        $this->assertSame(7, $product->fresh()->stock);

        $this->assertDatabaseHas('orders', [
            'id'             => $response->json('data.id'),
            'creator_id'     => $user->id,
            'source'         => 'pos',
            'status'         => 'completed',
            'payment_status' => 'paid',
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id'   => $response->json('data.id'),
            'product_id' => $product->id,
            'quantity'   => 3,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $response->json('data.id'),
            'provider' => 'cash',
            'channel'  => 'pos',
            'status'   => 'paid',
        ]);
    }

    #[Test]
    public function order_creation_with_insufficient_stock_returns_422_and_keeps_stock(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price'   => 5000,
            'stock'   => 2,
        ]);

        Sanctum::actingAs($user, ['pos:operate']);

        $response = $this->postJson('/api/pos/v1/orders', [
            'items'          => [['product_id' => $product->id, 'quantity' => 5]],
            'payment_method' => 'cash',
            'total'          => 25000,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertNotEmpty($response->json('message'));

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(0, Order::where('creator_id', $user->id)->count());
    }

    #[Test]
    public function order_creation_rejects_product_of_another_creator(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $other = User::factory()->create(['role' => 'createur']);
        $foreignProduct = Product::factory()->create(['user_id' => $other->id, 'stock' => 10]);

        Sanctum::actingAs($user, ['pos:operate']);

        $response = $this->postJson('/api/pos/v1/orders', [
            'items'          => [['product_id' => $foreignProduct->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'total'          => 5000,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertSame(10, $foreignProduct->fresh()->stock);
    }

    #[Test]
    public function order_creation_is_idempotent_with_offline_id(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $product = Product::factory()->create([
            'user_id' => $user->id,
            'price'   => 5000,
            'stock'   => 10,
        ]);

        Sanctum::actingAs($user, ['pos:operate']);

        $payload = [
            'items'          => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'total'          => 10000,
            'offline_id'     => 'offline-abc-123',
        ];

        $first = $this->postJson('/api/pos/v1/orders', $payload);
        $first->assertStatus(201);

        $second = $this->postJson('/api/pos/v1/orders', $payload);
        $second->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $first->json('data.id'));

        // Pas de doublon, stock décrémenté UNE seule fois.
        $this->assertSame(1, Order::where('offline_id', 'offline-abc-123')->count());
        $this->assertSame(8, $product->fresh()->stock);
    }

    // ─── Historique ──────────────────────────────────────────────────────────

    #[Test]
    public function orders_history_is_paginated_and_scoped_to_creator(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $product = Product::factory()->create(['user_id' => $user->id, 'price' => 5000, 'stock' => 100]);

        Sanctum::actingAs($user, ['pos:operate']);

        foreach (['a', 'b', 'c'] as $suffix) {
            $this->postJson('/api/pos/v1/orders', [
                'items'          => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'total'          => 5000,
                'offline_id'     => "hist-{$suffix}",
            ])->assertStatus(201);
        }

        $response = $this->getJson('/api/pos/v1/orders');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total', 3)
            ->assertJsonCount(3, 'data.data')
            ->assertJsonStructure(['data' => ['current_page', 'data', 'last_page', 'total']]);
    }

    // ─── Sync offline ────────────────────────────────────────────────────────

    #[Test]
    public function sync_is_idempotent_via_offline_id(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $product = Product::factory()->create(['user_id' => $user->id, 'price' => 5000, 'stock' => 50]);

        Sanctum::actingAs($user, ['pos:operate']);

        $orders = [
            [
                'offline_id'     => 'sync-001',
                'items'          => [['product_id' => $product->id, 'quantity' => 2]],
                'payment_method' => 'cash',
                'total'          => 10000,
            ],
            [
                'offline_id'     => 'sync-002',
                'items'          => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'monetbil',
                'monetbil_ref'   => 'MB-REF-1',
                'total'          => 5000,
            ],
        ];

        // Premier sync : tout est créé.
        $first = $this->postJson('/api/pos/v1/sync', ['orders' => $orders]);
        $first->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.synced_ids', ['sync-001', 'sync-002'])
            ->assertJsonPath('data.skipped_ids', []);

        $this->assertSame(47, $product->fresh()->stock);

        // Second sync identique : tout est skip, aucun doublon ni re-décrément.
        $second = $this->postJson('/api/pos/v1/sync', ['orders' => $orders]);
        $second->assertOk()
            ->assertJsonPath('data.synced_ids', [])
            ->assertJsonPath('data.skipped_ids', ['sync-001', 'sync-002']);

        $this->assertSame(47, $product->fresh()->stock);
        $this->assertSame(2, Order::where('creator_id', $user->id)->where('source', 'pos')->count());
    }

    #[Test]
    public function sync_mixes_new_duplicate_and_failed_sales(): void
    {
        ['user' => $user] = $this->makeSignatureCreator();
        $product = Product::factory()->create(['user_id' => $user->id, 'price' => 5000, 'stock' => 3]);

        Sanctum::actingAs($user, ['pos:operate']);

        // Vente déjà synchronisée précédemment.
        $this->postJson('/api/pos/v1/orders', [
            'items'          => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'total'          => 5000,
            'offline_id'     => 'dup-1',
        ])->assertStatus(201);

        $response = $this->postJson('/api/pos/v1/sync', ['orders' => [
            [
                'offline_id'     => 'dup-1', // doublon → skipped
                'items'          => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'total'          => 5000,
            ],
            [
                'offline_id'     => 'new-1', // nouveau → synced
                'items'          => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'cash',
                'total'          => 5000,
            ],
            [
                'offline_id'     => 'fail-1', // stock insuffisant → failed
                'items'          => [['product_id' => $product->id, 'quantity' => 99]],
                'payment_method' => 'cash',
                'total'          => 495000,
            ],
        ]]);

        $response->assertOk()
            ->assertJsonPath('data.synced_ids', ['new-1'])
            ->assertJsonPath('data.skipped_ids', ['dup-1'])
            ->assertJsonPath('data.failed.0.offline_id', 'fail-1');

        $this->assertSame(1, $product->fresh()->stock);
    }
}

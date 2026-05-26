<?php

namespace Tests\Feature\Account;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderDetailTest extends TestCase
{
    use RefreshDatabase;

    protected Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientRole = Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    private function client(): User
    {
        return User::factory()->create([
            'role_id' => $this->clientRole->id,
            'status'  => 'active',
        ]);
    }

    private function orderFor(User $user, array $attrs = []): Order
    {
        return Order::factory()->create(array_merge(['user_id' => $user->id], $attrs));
    }

    // ── ST-1 : affichage ─────────────────────────────────────────────────────

    #[Test]
    public function order_detail_loads_200_for_owner(): void
    {
        $client = $this->client();
        $order  = $this->orderFor($client, ['status' => 'pending']);

        $response = $this->actingAsWithContext($client)
            ->get("/profil/commandes/{$order->id}");

        $response->assertStatus(200);
        $response->assertViewIs('profile.order-detail');
        $response->assertViewHas('order');
    }

    #[Test]
    public function order_detail_returns_403_for_other_user(): void
    {
        $owner = $this->client();
        $other = $this->client();
        $order = $this->orderFor($owner, ['status' => 'pending']);

        $response = $this->actingAsWithContext($other)
            ->get("/profil/commandes/{$order->id}");

        $response->assertStatus(403);
    }

    // ── ST-3 : RE-COMMANDER ───────────────────────────────────────────────────

    #[Test]
    public function reorder_clears_cart_and_repopulates_from_order_items(): void
    {
        $client  = $this->client();
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true]);
        $order   = $this->orderFor($client, ['status' => 'cancelled']);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => $product->price,
            'status'     => 'active',
        ]);

        Cart::create(['user_id' => $client->id]);

        $response = $this->actingAsWithContext($client)
            ->post("/profil/commandes/{$order->id}/recommander");

        $response->assertRedirect(route('checkout.index'));
        $cart = Cart::where('user_id', $client->id)->first();
        $this->assertNotNull($cart);
        $this->assertEquals(1, $cart->items()->count());
        $this->assertEquals(2, $cart->items()->first()->quantity);
    }

    #[Test]
    public function reorder_excludes_out_of_stock_products_and_flashes_warning(): void
    {
        $client    = $this->client();
        $inStock   = Product::factory()->create(['stock' => 3, 'is_active' => true]);
        $outOfStock = Product::factory()->create(['stock' => 0, 'is_active' => true]);
        $order     = $this->orderFor($client, ['status' => 'cancelled']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $inStock->id, 'quantity' => 1, 'price' => $inStock->price, 'status' => 'active']);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $outOfStock->id, 'quantity' => 1, 'price' => $outOfStock->price, 'status' => 'active']);

        Cart::create(['user_id' => $client->id]);

        $response = $this->actingAsWithContext($client)
            ->post("/profil/commandes/{$order->id}/recommander");

        $response->assertRedirect(route('checkout.index'));
        $cart = Cart::where('user_id', $client->id)->first();
        $this->assertEquals(1, $cart->items()->count());
        $this->assertNotNull(session('reorder_warnings'));
    }

    #[Test]
    public function reorder_applies_valid_promo_to_session(): void
    {
        $client = $this->client();
        $promo  = PromoCode::create([
            'code'       => 'VALID20',
            'name'       => '20% off',
            'type'       => 'percentage',
            'value'      => 20,
            'min_amount' => 0,
            'is_active'  => true,
        ]);
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true, 'price' => 10000]);
        $order   = $this->orderFor($client, ['status' => 'cancelled', 'promo_code_id' => $promo->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 10000, 'status' => 'active']);

        Cart::create(['user_id' => $client->id]);

        $this->actingAsWithContext($client)
            ->post("/profil/commandes/{$order->id}/recommander");

        $this->assertEquals('VALID20', session('applied_promo_code_code'));
        $this->assertGreaterThan(0, session('applied_promo_discount'));
    }

    #[Test]
    public function reorder_clears_promo_session_when_promo_expired(): void
    {
        $client = $this->client();
        $promo  = PromoCode::create([
            'code'       => 'EXPIRED',
            'name'       => 'Expired',
            'type'       => 'percentage',
            'value'      => 10,
            'min_amount' => 0,
            'is_active'  => true,
            'expires_at' => now()->subDay(),
        ]);
        $product = Product::factory()->create(['stock' => 5, 'is_active' => true, 'price' => 10000]);
        $order   = $this->orderFor($client, ['status' => 'cancelled', 'promo_code_id' => $promo->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 10000, 'status' => 'active']);

        Cart::create(['user_id' => $client->id]);

        $this->actingAsWithContext($client)
            ->withSession(['applied_promo_code_code' => 'OLD'])
            ->post("/profil/commandes/{$order->id}/recommander");

        $this->assertNull(session('applied_promo_code_code'));
    }

    #[Test]
    public function reorder_forbidden_for_other_user(): void
    {
        $owner = $this->client();
        $other = $this->client();
        $order = $this->orderFor($owner, ['status' => 'cancelled']);

        $response = $this->actingAsWithContext($other)
            ->post("/profil/commandes/{$order->id}/recommander");

        $response->assertStatus(403);
    }

    // ── ST-4 : MODIFIER ───────────────────────────────────────────────────────

    #[Test]
    public function update_order_address_succeeds_for_pending(): void
    {
        $client = $this->client();
        $order  = $this->orderFor($client, ['status' => 'pending']);

        $response = $this->actingAsWithContext($client)
            ->patch("/profil/commandes/{$order->id}/modifier", [
                'customer_address' => '42 Avenue des Baobabs, Brazzaville',
            ]);

        $response->assertRedirect();
        $this->assertEquals('42 Avenue des Baobabs, Brazzaville', $order->fresh()->customer_address);
    }

    #[Test]
    public function update_order_rejected_when_not_pending(): void
    {
        $client = $this->client();
        $order  = $this->orderFor($client, ['status' => 'processing']);

        $response = $this->actingAsWithContext($client)
            ->patch("/profil/commandes/{$order->id}/modifier", [
                'customer_address' => 'Nouvelle adresse',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotEquals('Nouvelle adresse', $order->fresh()->customer_address);
    }

    // ── ST-5 : SUPPRIMER (archive) ────────────────────────────────────────────

    #[Test]
    public function archive_cancelled_order_succeeds(): void
    {
        $client = $this->client();
        $order  = $this->orderFor($client, ['status' => 'cancelled']);

        $response = $this->actingAsWithContext($client)
            ->delete("/profil/commandes/{$order->id}/archive");

        $response->assertRedirect();
        $this->assertEquals('archived', $order->fresh()->status);
    }

    #[Test]
    public function archive_rejected_when_order_not_cancelled(): void
    {
        $client = $this->client();
        $order  = $this->orderFor($client, ['status' => 'pending']);

        $response = $this->actingAsWithContext($client)
            ->delete("/profil/commandes/{$order->id}/archive");

        $response->assertStatus(422);
        $this->assertEquals('pending', $order->fresh()->status);
    }
}

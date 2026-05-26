<?php

namespace Tests\Feature\Checkout;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutFunnelTest extends TestCase
{
    use RefreshDatabase;

    protected Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientRole = Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    private function clientWithCart(): array
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $product = Product::factory()->create(['price' => 15000, 'stock' => 10, 'is_active' => true]);
        $cart = Cart::create(['user_id' => $client->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 15000]);
        return [$client, $product];
    }

    #[Test]
    public function checkout_loads_for_client_with_cart(): void
    {
        [$client] = $this->clientWithCart();

        $response = $this->actingAsWithContext($client)->get('/checkout');

        $response->assertStatus(200);
        $response->assertViewIs('frontend.checkout.index');
        $response->assertViewHas('subtotal');
        $response->assertViewHas('addresses');
    }

    #[Test]
    public function checkout_shows_saved_addresses_when_present(): void
    {
        [$client] = $this->clientWithCart();
        Address::factory()->create([
            'user_id'      => $client->id,
            'address_line_1' => '12 Rue des Mangues',
            'city'         => 'Brazzaville',
            'country'      => 'Congo',
            'is_default'   => true,
        ]);

        $response = $this->actingAsWithContext($client)->get('/checkout');

        $response->assertStatus(200);
        $response->assertSee('12 Rue des Mangues');
        $response->assertSee('Brazzaville');
    }

    #[Test]
    public function checkout_redirects_to_cart_when_empty(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        Cart::create(['user_id' => $client->id]);

        $response = $this->actingAsWithContext($client)->get('/checkout');

        $response->assertRedirect(route('cart.index'));
    }

    #[Test]
    public function apply_promo_stores_discount_in_session(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        PromoCode::create([
            'code'       => 'SAVE10',
            'name'       => 'Réduction 10%',
            'type'       => 'percentage',
            'value'      => 10,
            'min_amount' => 0,
            'is_active'  => true,
        ]);

        $response = $this->actingAsWithContext($client)
            ->postJson('/api/checkout/apply-promo', ['code' => 'SAVE10', 'total' => 20000]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertEquals('SAVE10', session('applied_promo_code_code'));
        $this->assertGreaterThan(0, session('applied_promo_discount'));
    }

    #[Test]
    public function apply_promo_rejects_invalid_code(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        $response = $this->actingAsWithContext($client)
            ->postJson('/api/checkout/apply-promo', ['code' => 'BADCODE', 'total' => 20000]);

        $response->assertStatus(400);
        $response->assertJson(['success' => false]);
    }

    #[Test]
    public function remove_promo_clears_session(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        $response = $this->actingAsWithContext($client)
            ->withSession([
                'applied_promo_code_id'       => 1,
                'applied_promo_code_code'      => 'SAVE10',
                'applied_promo_discount'       => 2000,
                'applied_promo_free_shipping'  => false,
            ])
            ->postJson('/api/checkout/remove-promo', []);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertNull(session('applied_promo_code_code'));
    }

    #[Test]
    public function cart_shows_applied_promo_from_session(): void
    {
        [$client] = $this->clientWithCart();

        $response = $this->actingAsWithContext($client)
            ->withSession([
                'applied_promo_code_code'     => 'SUMMER',
                'applied_promo_discount'      => 1500,
                'applied_promo_free_shipping' => false,
            ])
            ->get('/cart');

        $response->assertStatus(200);
        $response->assertSee('SUMMER');
        $response->assertSee('1 500');
    }

    #[Test]
    public function unauthenticated_user_redirected_from_checkout(): void
    {
        $response = $this->get('/checkout');

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }
}

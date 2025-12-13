<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'client']);
        $this->actingAs($this->user);
        
        $this->product = Product::factory()->create([
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function user_can_create_order_from_cart(): void
    {
        // Ajouter produit au panier
        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 2);

        $response = $this->post(route('checkout.place'), [
            'full_name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Test Street',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'card',
        ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'payment_method' => 'card',
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);
        
        // Vérifier le total (2 * 10000 + 2000 livraison = 22000)
        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertEquals(22000, $order->total_amount);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 10000,
        ]);
    }

    #[Test]
    public function order_creation_reduces_product_stock(): void
    {
        $initialStock = $this->product->stock;
        $quantity = 3;

        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, $quantity);

        $this->post(route('checkout.place'), [
            'full_name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Test Street',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'card',
        ]);

        $this->product->refresh();
        $this->assertEquals($initialStock - $quantity, $this->product->stock);
    }

    #[Test]
    public function cannot_create_order_with_insufficient_stock(): void
    {
        $this->product->update(['stock' => 2]);

        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 5); // Plus que le stock disponible

        $response = $this->post(route('checkout.place'), [
            'payment_method' => 'card',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '123456789',
            'customer_address' => '123 Test Street',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('orders', [
            'user_id' => $this->user->id,
        ]);
    }

    #[Test]
    public function order_total_is_calculated_correctly(): void
    {
        $product2 = Product::factory()->create([
            'price' => 5000,
            'stock' => 10,
        ]);

        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 2); // 2 * 10000 = 20000
        $cartService->add($product2, 3); // 3 * 5000 = 15000

        $this->post(route('checkout.place'), [
            'full_name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Test Street',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'card',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        // Sous-total : 20000 + 15000 = 35000, Livraison : 2000, Total : 37000
        $this->assertEquals(37000, $order->total_amount);
    }

    #[Test]
    public function order_has_unique_order_number(): void
    {
        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 1);

        $this->post(route('checkout.place'), [
            'full_name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Test Street',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'card',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('CMD-', $order->order_number);
    }

    #[Test]
    public function order_has_qr_token(): void
    {
        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 1);

        $this->post(route('checkout.place'), [
            'full_name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => '+242 06 123 45 67',
            'address_line1' => '123 Test Street',
            'city' => 'Brazzaville',
            'country' => 'Congo',
            'shipping_method' => 'home_delivery',
            'payment_method' => 'card',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order->qr_token);
        $this->assertNotEmpty($order->qr_token);
    }
}


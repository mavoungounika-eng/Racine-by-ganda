<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Cart\DatabaseCartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    /** @test */
    public function user_can_create_order_from_cart()
    {
        // Ajouter produit au panier
        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 2);

        $response = $this->post(route('checkout.place'), [
            'payment_method' => 'card',
            'address_id' => null,
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '123456789',
            'customer_address' => '123 Test Street',
        ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'total_amount' => 20000, // 2 * 10000
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'price' => 10000,
        ]);
    }

    /** @test */
    public function order_creation_reduces_product_stock()
    {
        $initialStock = $this->product->stock;
        $quantity = 3;

        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, $quantity);

        $this->post(route('checkout.place'), [
            'payment_method' => 'card',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '123456789',
            'customer_address' => '123 Test Street',
        ]);

        $this->product->refresh();
        $this->assertEquals($initialStock - $quantity, $this->product->stock);
    }

    /** @test */
    public function cannot_create_order_with_insufficient_stock()
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

    /** @test */
    public function order_total_is_calculated_correctly()
    {
        $product2 = Product::factory()->create([
            'price' => 5000,
            'stock' => 10,
        ]);

        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 2); // 2 * 10000 = 20000
        $cartService->add($product2, 3); // 3 * 5000 = 15000

        $this->post(route('checkout.place'), [
            'payment_method' => 'card',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '123456789',
            'customer_address' => '123 Test Street',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertEquals(35000, $order->total_amount); // 20000 + 15000
    }

    /** @test */
    public function order_has_unique_order_number()
    {
        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 1);

        $this->post(route('checkout.place'), [
            'payment_method' => 'card',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '123456789',
            'customer_address' => '123 Test Street',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order->order_number);
        $this->assertStringStartsWith('CMD-', $order->order_number);
    }

    /** @test */
    public function order_has_qr_token()
    {
        $cartService = app(DatabaseCartService::class);
        $cartService->add($this->product, 1);

        $this->post(route('checkout.place'), [
            'payment_method' => 'card',
            'customer_name' => $this->user->name,
            'customer_email' => $this->user->email,
            'customer_phone' => '123456789',
            'customer_address' => '123 Test Street',
        ]);

        $order = Order::where('user_id', $this->user->id)->first();
        $this->assertNotNull($order->qr_token);
        $this->assertNotEmpty($order->qr_token);
    }
}


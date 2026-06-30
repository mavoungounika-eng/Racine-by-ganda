<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderOriginalTotalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    private function makeOrder(float $total = 10000, float $shipping = 0): Order
    {
        $user = User::factory()->create(['role' => 'client', 'role_id' => 5]);
        return Order::factory()->create([
            'user_id'       => $user->id,
            'status'        => 'pending',
            'total_amount'  => $total,
            'shipping_cost' => $shipping,
        ]);
    }

    public function test_original_total_set_at_creation(): void
    {
        $order = $this->makeOrder(85000);

        $this->assertSame('85000.00', (string) $order->original_total);
    }

    public function test_original_total_not_overwritten_if_already_set(): void
    {
        $user = User::factory()->create(['role' => 'client', 'role_id' => 5]);
        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'total_amount'   => 50000,
            'original_total' => 75000,
        ]);

        $this->assertSame('75000.00', (string) $order->original_total);
    }

    public function test_original_total_preserved_after_cancel_globally(): void
    {
        $order = $this->makeOrder(85000);
        $product = Product::factory()->create(['price' => 85000, 'stock' => 10, 'is_active' => true]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'price'      => 85000,
            'status'     => OrderItem::STATUS_ACTIVE,
        ]);

        $originalTotal = $order->original_total;
        $order->cancelGlobally();
        $order->refresh();

        $this->assertSame($originalTotal, $order->original_total);
    }

    public function test_original_total_preserved_after_restore(): void
    {
        $order = $this->makeOrder(85000);
        $product = Product::factory()->create(['price' => 85000, 'stock' => 10, 'is_active' => true]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'price'      => 85000,
            'status'     => OrderItem::STATUS_ACTIVE,
        ]);
        $order = $order->fresh();
        $originalTotal = $order->original_total;

        $order->cancelGlobally();
        $order->restore();
        $order->refresh();

        $this->assertSame($originalTotal, $order->original_total);
    }

    public function test_recalculate_total_does_not_touch_original_total(): void
    {
        $order = $this->makeOrder(85000, 2000);
        $product = Product::factory()->create(['price' => 42500, 'stock' => 10, 'is_active' => true]);
        OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 2,
            'price'      => 42500,
            'status'     => OrderItem::STATUS_ACTIVE,
        ]);
        $order = $order->fresh();

        $order->recalculateTotal();
        $order->refresh();

        // original_total unchanged — total_amount reflects items + shipping
        $this->assertSame('85000.00', (string) $order->original_total);
        $this->assertSame('87000.00', (string) $order->total_amount); // 85000 + 2000 shipping
    }
}

<?php

namespace Modules\ERP\Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = new StockService();
    }

    /** @test */
    public function it_decrements_stock_from_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'paid']);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 1000,
        ]);

        $this->stockService->decrementFromOrder($order);

        $product->refresh();
        $this->assertEquals(7, $product->stock);

        $this->assertDatabaseHas('erp_stock_movements', [
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
        ]);
    }

    /** @test */
    public function it_restocks_from_cancelled_order()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $order = Order::factory()->create(['user_id' => $user->id, 'status' => 'cancelled']);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 1000,
        ]);

        $this->stockService->restockFromOrder($order);

        $product->refresh();
        $this->assertEquals(7, $product->stock);

        $this->assertDatabaseHas('erp_stock_movements', [
            'stockable_type' => Product::class,
            'stockable_id' => $product->id,
            'type' => 'in',
            'quantity' => 2,
        ]);
    }

    /** @test */
    public function it_handles_order_without_items()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Ne doit pas lever d'exception
        $this->stockService->decrementFromOrder($order);
        $this->stockService->restockFromOrder($order);

        $this->assertTrue(true);
    }
}


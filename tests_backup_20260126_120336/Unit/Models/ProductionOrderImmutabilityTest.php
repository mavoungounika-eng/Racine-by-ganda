<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Exceptions\Production\ImmutableOrderException;

class ProductionOrderImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_prevents_modifying_completed_order()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->expectException(ImmutableOrderException::class);
        $this->expectExceptionMessage('Cannot modify completed order');

        $order->update(['notes' => 'Trying to change a completed order']);
    }

    /** @test */
    public function it_prevents_deleting_completed_order()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->expectException(ImmutableOrderException::class);
        $this->expectExceptionMessage('Cannot delete completed order');

        $order->delete();
    }

    /** @test */
    public function it_prevents_modifying_bom_snapshot_after_creation()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'bom_snapshot' => [
                'version' => '1.0',
                'materials' => [
                    ['type' => 'fabric', 'unit_cost' => 3500],
                ],
            ],
        ]);

        $this->expectException(ImmutableOrderException::class);
        $this->expectExceptionMessage('Cannot modify BOM snapshot');

        $order->update([
            'bom_snapshot' => [
                'version' => '2.0', // Trying to change
                'materials' => [],
            ],
        ]);
    }

    /** @test */
    public function it_allows_modifying_non_completed_order()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        // Should NOT throw exception
        $order->update(['notes' => 'This is allowed']);

        $this->assertEquals('This is allowed', $order->fresh()->notes);
    }

    /** @test */
    public function it_allows_deleting_non_completed_order()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'draft',
        ]);

        // Should NOT throw exception
        $order->delete();

        $this->assertSoftDeleted('production_orders', ['id' => $order->id]);
    }

    /** @test */
    public function it_allows_setting_bom_snapshot_on_creation()
    {
        $product = Product::factory()->create();
        
        // Should NOT throw exception
        $order = ProductionOrder::create([
            'of_number' => 'OF-TEST-001',
            'product_id' => $product->id,
            'target_quantity' => 50,
            'deadline_date' => now()->addDays(7),
            'bom_snapshot' => [
                'version' => '1.0',
                'materials' => [],
            ],
        ]);

        $this->assertNotNull($order->bom_snapshot);
        $this->assertEquals('1.0', $order->bom_snapshot['version']);
    }
}

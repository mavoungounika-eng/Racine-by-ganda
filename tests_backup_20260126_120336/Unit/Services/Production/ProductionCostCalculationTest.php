<?php

namespace Tests\Unit\Services\Production;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOutput;
use App\Services\Production\ProductionService;
use App\Exceptions\Production\InvalidOrderStateException;
use App\Exceptions\Production\MissingBOMSnapshotException;

class ProductionCostCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected ProductionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductionService();
    }

    /** @test */
    public function it_calculates_cost_only_from_bom_snapshot()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'completed',
            'completed_at' => now(),
            'bom_snapshot' => [
                'version' => '1.0',
                'materials' => [
                    ['type' => 'fabric', 'unit_cost' => 3500, 'unit_consumption' => 1.5],
                ],
                'total_material_cost_standard' => 5250,
            ],
        ]);

        // Create outputs
        $order->outputs()->create([
            'product_id' => $product->id,
            'variant_sku' => 'TEST-S',
            'qty_good' => 10,
            'qty_second' => 0,
            'qty_rejected' => 0,
        ]);

        // Add material logs
        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'material_reference' => 'TEST-001',
            'quantity_used' => 15,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        $cost = $this->service->calculateRealCost($order);

        // Verify calculation uses snapshot
        $this->assertArrayHasKey('material_cost', $cost);
        $this->assertArrayHasKey('labor_cost', $cost);
        $this->assertArrayHasKey('total_cost', $cost);
        $this->assertArrayHasKey('unit_cost', $cost);
        $this->assertArrayHasKey('bom_version', $cost);
        $this->assertEquals('1.0', $cost['bom_version']);
    }

    /** @test */
    public function it_cannot_calculate_cost_for_non_completed_order()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress', // Not completed
            'bom_snapshot' => ['version' => '1.0'],
        ]);

        $this->expectException(InvalidOrderStateException::class);
        $this->expectExceptionMessage('non-completed order');

        $this->service->calculateRealCost($order);
    }

    /** @test */
    public function it_throws_exception_if_bom_snapshot_missing()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'completed',
            'completed_at' => now(),
            'bom_snapshot' => null, // Missing snapshot
        ]);

        $this->expectException(MissingBOMSnapshotException::class);
        $this->expectExceptionMessage('has no BOM snapshot');

        $this->service->calculateRealCost($order);
    }

    /** @test */
    public function it_calculates_unit_cost_correctly()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'completed',
            'completed_at' => now(),
            'bom_snapshot' => ['version' => '1.0'],
        ]);

        // Create outputs with 50 good pieces
        $order->outputs()->create([
            'product_id' => $product->id,
            'variant_sku' => 'TEST-S',
            'qty_good' => 30,
            'qty_second' => 0,
            'qty_rejected' => 0,
        ]);
        $order->outputs()->create([
            'product_id' => $product->id,
            'variant_sku' => 'TEST-M',
            'qty_good' => 20,
            'qty_second' => 0,
            'qty_rejected' => 0,
        ]);

        // Add material logs (10 units * 10 XAF = 100)
        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'quantity_used' => 10,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        // Add time logs (60 minutes = 1 hour * 2000 XAF = 2000)
        $operation = $order->operations()->create([
            'name' => 'Cutting',
            'sequence_order' => 1,
        ]);
        $operation->timeLogs()->create([
            'operator_id' => 1,
            'duration_minutes' => 60,
        ]);

        $cost = $this->service->calculateRealCost($order);

        // Total cost = 100 + 2000 = 2100
        // Unit cost = 2100 / 50 = 42
        $this->assertEquals(100, $cost['material_cost']);
        $this->assertEquals(2000, $cost['labor_cost']);
        $this->assertEquals(2100, $cost['total_cost']);
        $this->assertEquals(42, $cost['unit_cost']);
        $this->assertEquals(50, $cost['good_quantity']);
    }
}

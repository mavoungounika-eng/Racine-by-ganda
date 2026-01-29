<?php

namespace Tests\Unit\Services\Production;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOperation;
use App\Services\Production\ProductionService;
use App\Exceptions\Production\InvalidOrderStateException;
use App\Exceptions\Production\MissingProductionDataException;
use App\Exceptions\Production\InvalidProductionOutputException;

class ProductionOrderClosureTest extends TestCase
{
    use RefreshDatabase;

    protected ProductionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductionService();
    }

    /** @test */
    public function it_cannot_close_order_without_material_logs()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        $this->expectException(MissingProductionDataException::class);
        $this->expectExceptionMessage('No material consumption logged');

        $this->service->closeOrder($order, [
            ['variant_sku' => 'TEST-S', 'qty_good' => 10],
        ]);
    }

    /** @test */
    public function it_cannot_close_order_without_time_logs()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        // Add operation (makes time logs required)
        $order->operations()->create([
            'name' => 'Cutting',
            'sequence_order' => 1,
            'standard_time_minutes' => 120,
        ]);

        // Add material logs (satisfies R1)
        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'material_reference' => 'TEST-001',
            'quantity_used' => 10,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        $this->expectException(MissingProductionDataException::class);
        $this->expectExceptionMessage('No time logs recorded');

        $this->service->closeOrder($order, [
            ['variant_sku' => 'TEST-S', 'qty_good' => 10],
        ]);
    }

    /** @test */
    public function it_cannot_close_order_without_outputs()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'material_reference' => 'TEST-001',
            'quantity_used' => 10,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        $this->expectException(MissingProductionDataException::class);
        $this->expectExceptionMessage('No outputs provided');

        $this->service->closeOrder($order, []); // Empty outputs
    }

    /** @test */
    public function it_rejects_output_with_zero_total_quantity()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'material_reference' => 'TEST-001',
            'quantity_used' => 10,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        $this->expectException(InvalidProductionOutputException::class);
        $this->expectExceptionMessage('zero total quantity');

        $this->service->closeOrder($order, [
            [
                'variant_sku' => 'TEST-S',
                'qty_good' => 0,
                'qty_second' => 0,
                'qty_rejected' => 0,
            ],
        ]);
    }

    /** @test */
    public function it_cannot_close_order_not_in_progress()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'draft', // Wrong status
        ]);

        $this->expectException(InvalidOrderStateException::class);
        $this->expectExceptionMessage("Status must be 'in_progress'");

        $this->service->closeOrder($order, [
            ['variant_sku' => 'TEST-S', 'qty_good' => 10],
        ]);
    }

    /** @test */
    public function it_rejects_output_missing_variant_sku()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'material_reference' => 'TEST-001',
            'quantity_used' => 10,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        $this->expectException(InvalidProductionOutputException::class);
        $this->expectExceptionMessage('variant_sku is required');

        $this->service->closeOrder($order, [
            ['qty_good' => 10], // Missing variant_sku
        ]);
    }

    /** @test */
    public function it_successfully_closes_order_with_valid_data()
    {
        $product = Product::factory()->create();
        $order = ProductionOrder::factory()->create([
            'product_id' => $product->id,
            'status' => 'in_progress',
        ]);

        $operation = $order->operations()->create([
            'name' => 'Cutting',
            'sequence_order' => 1,
            'standard_time_minutes' => 120,
        ]);

        $order->materialLogs()->create([
            'material_type' => 'fabric',
            'material_reference' => 'TEST-001',
            'quantity_used' => 10,
            'unit' => 'm',
            'logged_by' => 1,
        ]);

        $operation->timeLogs()->create([
            'operator_id' => 1,
            'duration_minutes' => 130,
        ]);

        $closedOrder = $this->service->closeOrder($order, [
            ['variant_sku' => 'TEST-S', 'qty_good' => 10, 'qty_second' => 1, 'qty_rejected' => 0],
            ['variant_sku' => 'TEST-M', 'qty_good' => 15, 'qty_second' => 0, 'qty_rejected' => 2],
        ]);

        $this->assertEquals('completed', $closedOrder->status);
        $this->assertNotNull($closedOrder->completed_at);
        $this->assertCount(2, $closedOrder->outputs);
        $this->assertEquals(25, $closedOrder->produced_qty_good); // 10 + 15
        $this->assertEquals(1, $closedOrder->produced_qty_second);
        $this->assertEquals(2, $closedOrder->rejected_qty);
    }
}

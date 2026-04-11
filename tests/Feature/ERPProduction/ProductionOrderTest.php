<?php

namespace Tests\Feature\ERPProduction;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\WorkStep;
use Modules\ERPProduction\Models\WorkCenter;
use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Services\ProductionOrderService;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpRawMaterial;

class ProductionOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Bom $bom;
    protected ProductionOrderService $productionOrderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create(['name' => 'Robe Pagne Luxe']);
        
        // CrÃ©er BOM avec items
        $this->bom = Bom::create([
            'product_id' => $this->product->id,
            'version' => '1.0',
            'name' => 'BOM Robe Pagne',
            'quantity' => 1.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        $rawMaterial = ErpRawMaterial::factory()->create();
        $this->bom->items()->create([
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5,
            'unit' => 'meter',
        ]);

        $this->productionOrderService = app(ProductionOrderService::class);
    }
    #[Test]
    public function it_can_create_production_order()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->assertInstanceOf(ProductionOrder::class, $order);
        $this->assertEquals('draft', $order->status);
        $this->assertEquals(10, $order->quantity_planned);
        $this->assertEquals($this->product->id, $order->product_id);
        $this->assertEquals($this->bom->id, $order->bom_id);
        $this->assertStringStartsWith('PO-', $order->order_number);
    }
    #[Test]
    public function it_transitions_from_draft_to_planned()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            5
        );

        $this->assertEquals('draft', $order->status);

        $plannedOrder = $this->productionOrderService->planProductionOrder($order);

        $this->assertEquals('planned', $plannedOrder->status);
    }
    #[Test]
    public function it_generates_work_steps_automatically_when_planned()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            5
        );

        $this->assertCount(0, $order->steps);

        $this->productionOrderService->planProductionOrder($order);

        $order->refresh();
        $this->assertGreaterThan(0, $order->steps->count());
        
        // VÃ©rifier Ã©tapes standard
        $stepNames = $order->steps->pluck('name')->toArray();
        $this->assertContains('Coupe tissu', $stepNames);
        $this->assertContains('Assemblage/Couture', $stepNames);
        $this->assertContains('Finitions', $stepNames);
        $this->assertContains('Contrôle qualité', $stepNames);
    }
    #[Test]
    public function it_can_start_production()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            5
        );

        $this->productionOrderService->planProductionOrder($order);
        
        $startedOrder = $this->productionOrderService->startProductionOrder($order->fresh());

        $this->assertEquals('in_progress', $startedOrder->status);
        $this->assertNotNull($startedOrder->actual_start_date);
    }
    #[Test]
    public function it_completes_work_steps_sequentially()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            5
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $order->refresh();
        $steps = $order->steps()->orderBy('sequence')->get();

        // DÃ©marrer premiÃ¨re Ã©tape
        $firstStep = $steps->first();
        $this->assertTrue($firstStep->canStart());
        
        $this->productionOrderService->startWorkStep($firstStep);
        $this->assertEquals('in_progress', $firstStep->fresh()->status);

        // ComplÃ©ter premiÃ¨re Ã©tape
        $this->productionOrderService->completeWorkStep($firstStep->fresh());
        $this->assertEquals('completed', $firstStep->fresh()->status);

        // DeuxiÃ¨me Ã©tape peut maintenant dÃ©marrer
        $secondStep = $steps->skip(1)->first();
        $this->assertTrue($secondStep->canStart());
    }
    #[Test]
    public function it_can_finish_production()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $finishedOrder = $this->productionOrderService->finishProductionOrder(
            $order->fresh(),
            9, // QuantitÃ© produite
            1  // QuantitÃ© rejetÃ©e
        );

        $this->assertEquals('finished', $finishedOrder->status);
        $this->assertEquals(9, $finishedOrder->quantity_produced);
        $this->assertEquals(1, $finishedOrder->quantity_rejected);
        $this->assertNotNull($finishedOrder->actual_end_date);
    }
    #[Test]
    public function it_calculates_progress_percentage()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            5
        );

        $this->productionOrderService->planProductionOrder($order);
        $order->refresh();

        // Aucune Ã©tape complÃ©tÃ©e
        $this->assertEquals(0, $order->getProgressPercentage());

        // ComplÃ©ter premiÃ¨re Ã©tape
        $firstStep = $order->steps()->orderBy('sequence')->first();
        $firstStep->update(['status' => 'completed']);

        $order->refresh();
        $totalSteps = $order->steps()->count();
        $expectedProgress = (1 / $totalSteps) * 100;
        
        $this->assertEquals($expectedProgress, $order->getProgressPercentage());
    }
    #[Test]
    public function it_prevents_starting_order_without_complete_bom()
    {
        // BOM sans items
        $incompleteBom = Bom::create([
            'product_id' => $this->product->id,
            'version' => '2.0',
            'name' => 'BOM Incomplete',
            'quantity' => 1.00,
            'is_active' => true,
        ]);

        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $incompleteBom->id,
            5
        );

        $this->productionOrderService->planProductionOrder($order);

        $this->assertFalse($order->fresh()->canStart());
    }
    #[Test]
    public function it_tracks_actual_duration()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            5
        );

        $this->productionOrderService->planProductionOrder($order);
        
        $startedOrder = $this->productionOrderService->startProductionOrder($order->fresh());
        
        // Simuler passage du temps
        sleep(1);
        
        $finishedOrder = $this->productionOrderService->finishProductionOrder(
            $startedOrder->fresh(),
            5
        );

        $duration = $finishedOrder->getActualDuration();
        $this->assertNotNull($duration);
        $this->assertGreaterThan(0, $duration);
    }
}

<?php

namespace Tests\Feature\ERPProduction;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Models\ProductionCost;
use Modules\ERPProduction\Services\ProductionOrderService;
use Modules\ERPProduction\Services\QualityControlService;
use Modules\ERPProduction\Services\CostingService;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpRawMaterial;
use Illuminate\Support\Facades\Event;

class CostingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Bom $bom;
    protected ProductionOrderService $productionOrderService;
    protected QualityControlService $qualityControlService;
    protected CostingService $costingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create(['name' => 'Robe Pagne Luxe']);
        
        // Créer BOM
        $this->bom = Bom::create([
            'product_id' => $this->product->id,
            'version' => '1.0',
            'name' => 'BOM Robe Pagne',
            'quantity' => 1.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        $rawMaterial = ErpRawMaterial::factory()->create([
            'name' => 'Tissu Wax',
            'unit_cost' => 5000, // 5000 FCFA/m
        ]);
        
        $this->bom->items()->create([
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5, // 2.5m × 5000 = 12,500 FCFA/unité
            'unit' => 'meter',
            'waste_percentage' => 0,
        ]);

        $this->productionOrderService = app(ProductionOrderService::class);
        $this->qualityControlService = app(QualityControlService::class);
        $this->costingService = app(CostingService::class);
    }

    /** @test */
    public function it_calculates_real_cost_without_losses()
    {
        $order = $this->createAndCompleteOrder(10, 10, 0, 0);

        $cost = $this->costingService->calculateForOrder($order);

        $this->assertInstanceOf(ProductionCost::class, $cost);
        $this->assertEquals(12500, $cost->theoretical_unit_cost); // 2.5m × 5000
        $this->assertEquals(12500, $cost->actual_unit_cost); // Aucune perte
        $this->assertEquals(0, $cost->cost_variance);
        $this->assertEquals(100, $cost->yield_rate); // 10/10 × 100
    }

    /** @test */
    public function it_includes_scrap_cost_in_calculation()
    {
        $order = $this->createAndCompleteOrder(10, 8, 0, 2);

        $cost = $this->costingService->calculateForOrder($order);

        // Coût matières: 10 × 12,500 = 125,000
        // Coût rebuts: 2 × 12,500 = 25,000
        // Total: 150,000
        // Coût unitaire: 150,000 / 8 = 18,750

        $this->assertEquals(12500, $cost->theoretical_unit_cost);
        $this->assertEquals(18750, $cost->actual_unit_cost);
        $this->assertEquals(6250, $cost->cost_variance); // 18,750 - 12,500
        $this->assertEquals(80, $cost->yield_rate); // 8/10 × 100
    }

    /** @test */
    public function it_includes_rework_cost_in_calculation()
    {
        $order = $this->createAndCompleteOrder(10, 9, 1, 0);

        $cost = $this->costingService->calculateForOrder($order);

        // Coût matières: 10 × 12,500 = 125,000
        // Coût reprises: 1 × 12,500 × 0.5 = 6,250
        // Total: 131,250
        // Coût unitaire: 131,250 / 9 = 14,583.33

        $this->assertEquals(12500, $cost->theoretical_unit_cost);
        $this->assertEquals(14583.33, round($cost->actual_unit_cost, 2));
        $this->assertEquals(90, $cost->yield_rate); // 9/10 × 100
    }

    /** @test */
    public function it_compares_bom_vs_real()
    {
        $order = $this->createAndCompleteOrder(10, 7, 2, 1);

        $cost = $this->costingService->calculateForOrder($order);

        $summary = $cost->getSummary();

        $this->assertEquals(12500, $summary['theoretical_unit_cost']);
        $this->assertGreaterThan($summary['theoretical_unit_cost'], $summary['actual_unit_cost']);
        $this->assertTrue($summary['is_over_budget']);
        $this->assertGreaterThan(0, $summary['variance_percentage']);
    }

    /** @test */
    public function it_dispatches_production_cost_calculated_event()
    {
        Event::fake([ProductionCostCalculated::class]);

        $order = $this->createAndCompleteOrder(10, 10, 0, 0);

        $this->costingService->calculateForOrder($order);

        Event::assertDispatched(ProductionCostCalculated::class);
    }

    /** @test */
    public function it_blocks_costing_if_quality_check_missing()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());
        $this->productionOrderService->finishProductionOrder($order->fresh(), 10);
        $this->productionOrderService->closeProductionOrder($order->fresh());

        // Pas de contrôle qualité
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Quality check required');

        $this->costingService->calculateForOrder($order->fresh());
    }

    /** @test */
    public function it_blocks_costing_if_wip_not_closed()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Ordre pas terminé
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('must be closed');

        $this->costingService->calculateForOrder($order->fresh());
    }

    /** @test */
    public function it_validates_quantity_coherence()
    {
        $order = $this->createAndCompleteOrder(10, 8, 1, 1);

        $cost = $this->costingService->calculateForOrder($order);

        // Vérifier cohérence: planifié = produit + repris + rejeté
        $qualityChecks = $order->qualityChecks;
        $totalChecked = $qualityChecks->sum('quantity_checked');
        $totalPassed = $qualityChecks->sum('quantity_passed');
        $totalReworked = $qualityChecks->sum('quantity_reworked');
        $totalRejected = $qualityChecks->sum('quantity_rejected');

        $this->assertEquals(
            $totalChecked,
            $totalPassed + $totalReworked + $totalRejected
        );
    }

    /**
     * Helper: Créer et compléter ordre production
     */
    protected function createAndCompleteOrder(
        int $quantityPlanned,
        int $quantityPassed,
        int $quantityReworked,
        int $quantityRejected
    ): ProductionOrder {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            $quantityPlanned
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Contrôle qualité
        $this->qualityControlService->inspectOrder($order->fresh(), [
            'status' => $quantityRejected > 0 ? 'reject' : ($quantityReworked > 0 ? 'rework' : 'pass'),
            'quantity_checked' => $quantityPlanned,
            'quantity_passed' => $quantityPassed,
            'quantity_reworked' => $quantityReworked,
            'quantity_rejected' => $quantityRejected,
        ]);

        $quantityProduced = $quantityPassed + $quantityReworked;
        $this->productionOrderService->finishProductionOrder($order->fresh(), $quantityProduced, $quantityRejected);
        $this->productionOrderService->closeProductionOrder($order->fresh());

        return $order->fresh(['bom.items.rawMaterial', 'qualityChecks']);
    }
}

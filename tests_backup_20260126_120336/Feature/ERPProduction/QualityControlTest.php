<?php

namespace Tests\Feature\ERPProduction;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Models\QualityCheck;
use Modules\ERPProduction\Models\QualityDefect;
use Modules\ERPProduction\Services\ProductionOrderService;
use Modules\ERPProduction\Services\QualityControlService;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpRawMaterial;
use Illuminate\Support\Facades\Event;

class QualityControlTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Bom $bom;
    protected ProductionOrderService $productionOrderService;
    protected QualityControlService $qualityControlService;

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

        $rawMaterial = ErpRawMaterial::factory()->create();
        $this->bom->items()->create([
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5,
            'unit' => 'meter',
        ]);

        $this->productionOrderService = app(ProductionOrderService::class);
        $this->qualityControlService = app(QualityControlService::class);
    }

    /** @test */
    public function it_can_inspect_complete_production_order()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $check = $this->qualityControlService->inspectOrder($order, [
            'status' => 'pass',
            'quantity_checked' => 10,
            'quantity_passed' => 9,
            'quantity_reworked' => 1,
            'quantity_rejected' => 0,
        ]);

        $this->assertInstanceOf(QualityCheck::class, $check);
        $this->assertEquals('pass', $check->status);
        $this->assertEquals(10, $check->quantity_checked);
        $this->assertEquals(9, $check->quantity_passed);
        $this->assertEquals(90, $check->getPassRate());
    }

    /** @test */
    public function it_blocks_on_critical_defect()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $check = $this->qualityControlService->reject([
            'production_order_id' => $order->id,
            'quantity_checked' => 10,
            'quantity_passed' => 0,
            'quantity_reworked' => 0,
            'quantity_rejected' => 10,
        ]);

        // Enregistrer défaut critique
        $this->qualityControlService->recordDefect($check, [
            'defect_code' => 'CRIT-001',
            'defect_category' => 'material',
            'severity' => 'critical',
            'description' => 'Tissu défectueux',
        ]);

        $this->assertTrue($check->hasCriticalDefect());
        $this->assertTrue($this->qualityControlService->hasBlockingDefect($order));
        $this->assertFalse($this->qualityControlService->canProceedToFinish($order));
    }

    /** @test */
    public function it_handles_rework_with_wip_return()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $check = $this->qualityControlService->markForRework([
            'production_order_id' => $order->id,
            'quantity_checked' => 10,
            'quantity_passed' => 7,
            'quantity_reworked' => 3,
            'quantity_rejected' => 0,
        ]);

        $this->assertEquals('rework', $check->status);
        $this->assertEquals(3, $check->quantity_reworked);
        $this->assertEquals(30, $check->getReworkRate());
    }

    /** @test */
    public function it_handles_reject_with_traceability()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $check = $this->qualityControlService->reject([
            'production_order_id' => $order->id,
            'quantity_checked' => 10,
            'quantity_passed' => 0,
            'quantity_reworked' => 0,
            'quantity_rejected' => 10,
            'notes' => 'Lot complet défectueux',
        ]);

        // Enregistrer causes
        $this->qualityControlService->recordDefect($check, [
            'defect_code' => 'MAT-001',
            'defect_category' => 'material',
            'severity' => 'major',
            'description' => 'Tissu déchiré',
        ]);

        $this->qualityControlService->recordDefect($check, [
            'defect_code' => 'PROC-002',
            'defect_category' => 'process',
            'severity' => 'minor',
            'description' => 'Couture irrégulière',
        ]);

        $this->assertEquals('reject', $check->status);
        $this->assertEquals(100, $check->getRejectionRate());
        $this->assertCount(2, $check->defects);
    }

    /** @test */
    public function it_dispatches_quality_check_events()
    {
        Event::fake([
            QualityCheckPassed::class,
            QualityCheckReworked::class,
            QualityCheckRejected::class,
        ]);

        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        // Pass
        $this->qualityControlService->approve([
            'production_order_id' => $order->id,
            'quantity_checked' => 10,
        ]);

        Event::assertDispatched(QualityCheckPassed::class);

        // Rework
        $this->qualityControlService->markForRework([
            'production_order_id' => $order->id,
            'quantity_checked' => 5,
            'quantity_passed' => 3,
            'quantity_reworked' => 2,
            'quantity_rejected' => 0,
        ]);

        Event::assertDispatched(QualityCheckReworked::class);

        // Reject
        $this->qualityControlService->reject([
            'production_order_id' => $order->id,
            'quantity_checked' => 2,
            'quantity_passed' => 0,
            'quantity_reworked' => 0,
            'quantity_rejected' => 2,
        ]);

        Event::assertDispatched(QualityCheckRejected::class);
    }

    /** @test */
    public function it_prevents_closure_without_quality_check()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        // Aucun contrôle qualité
        $this->assertFalse($this->qualityControlService->canProceedToFinish($order));

        // Après contrôle
        $this->qualityControlService->approve([
            'production_order_id' => $order->id,
            'quantity_checked' => 10,
        ]);

        $this->assertTrue($this->qualityControlService->canProceedToFinish($order));
    }

    /** @test */
    public function it_validates_quantity_coherence()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $check = $this->qualityControlService->inspectOrder($order, [
            'status' => 'pass',
            'quantity_checked' => 10,
            'quantity_passed' => 7,
            'quantity_reworked' => 2,
            'quantity_rejected' => 1,
        ]);

        // Vérifier contrainte: checked = passed + reworked + rejected
        $this->assertEquals(
            $check->quantity_checked,
            $check->quantity_passed + $check->quantity_reworked + $check->quantity_rejected
        );
    }

    /** @test */
    public function it_calculates_quality_summary()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        // Contrôle 1
        $this->qualityControlService->approve([
            'production_order_id' => $order->id,
            'quantity_checked' => 10,
        ]);

        // Contrôle 2 (reprise)
        $this->qualityControlService->markForRework([
            'production_order_id' => $order->id,
            'quantity_checked' => 5,
            'quantity_passed' => 4,
            'quantity_reworked' => 1,
            'quantity_rejected' => 0,
        ]);

        $summary = $this->qualityControlService->getQualitySummary($order);

        $this->assertEquals(2, $summary['total_checks']);
        $this->assertEquals(15, $summary['total_checked']); // 10 + 5
        $this->assertEquals(14, $summary['total_passed']); // 10 + 4
        $this->assertEquals(1, $summary['total_reworked']);
        $this->assertEquals(0, $summary['total_rejected']);
        $this->assertEquals(93.33, $summary['pass_rate']); // 14/15 * 100
    }
}

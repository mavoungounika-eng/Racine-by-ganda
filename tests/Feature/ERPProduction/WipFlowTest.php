<?php

namespace Tests\Feature\ERPProduction;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Models\WipMovement;
use Modules\ERPProduction\Services\ProductionOrderService;
use Modules\ERPProduction\Services\WipService;
use Modules\Accounting\Models\AccountingEntry;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpRawMaterial;
use Illuminate\Support\Facades\Event;
use Tests\Traits\SeedsAccounting;

class WipFlowTest extends TestCase
{
    use RefreshDatabase, SeedsAccounting;

    protected User $user;
    protected Product $product;
    protected Bom $bom;
    protected ProductionOrderService $productionOrderService;
    protected WipService $wipService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->seedAccounting();

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

        $rawMaterial = ErpRawMaterial::factory()->create([
            'name' => 'Tissu Wax',
            'unit_cost' => 5000,
        ]);
        
        $this->bom->items()->create([
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5,
            'unit' => 'meter',
        ]);

        $this->productionOrderService = app(ProductionOrderService::class);
        $this->wipService = app(WipService::class);
    }
    #[Test]
    public function it_creates_wip_movement_when_production_starts()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Enregistrer dÃ©marrage WIP
        $movement = $this->wipService->startProduction($order->fresh());

        $this->assertInstanceOf(WipMovement::class, $movement);
        $this->assertEquals('production_started', $movement->type);
        $this->assertEquals(10, $movement->quantity);
    }
    #[Test]
    public function it_tracks_wip_movements_through_production()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // DÃ©marrage
        $this->wipService->startProduction($order->fresh());

        // ComplÃ©ter Ã©tapes
        $steps = $order->fresh()->steps;
        foreach ($steps as $step) {
            if ($step->canStart()) {
                $this->productionOrderService->startWorkStep($step);
                $this->productionOrderService->completeWorkStep($step->fresh());
                $this->wipService->completeStep($step->fresh(), 10);
            }
        }

        // Fin production
        $this->productionOrderService->finishProductionOrder($order->fresh(), 9, 1);
        $this->wipService->finishProduction($order->fresh());

        // VÃ©rifier mouvements
        $movements = $this->wipService->getMovements($order->fresh());
        $this->assertGreaterThan(0, $movements->count());
        
        // VÃ©rifier types
        $this->assertTrue($movements->contains('type', 'production_started'));
        $this->assertTrue($movements->contains('type', 'production_finished'));
    }
    #[Test]
    public function it_calculates_wip_balance_correctly()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->wipService->startProduction($order->fresh());

        // Balance aprÃ¨s dÃ©marrage
        $balance = $this->wipService->getWipBalance($order->fresh());
        $this->assertEquals(10, $balance['started']);
        $this->assertEquals(0, $balance['finished']);
        $this->assertEquals(10, $balance['in_progress']);

        // Fin production
        $this->productionOrderService->finishProductionOrder($order->fresh(), 9, 1);
        $this->wipService->finishProduction($order->fresh());

        // Balance aprÃ¨s fin
        $balance = $this->wipService->getWipBalance($order->fresh());
        $this->assertEquals(10, $balance['started']);
        $this->assertEquals(9, $balance['finished']);
        $this->assertEquals(1, $balance['in_progress']); // 10 - 9 = 1 (en attente ou rebut)
    }
    #[Test]
    public function it_dispatches_production_started_event()
    {
        Event::fake([\Modules\ERPProduction\Events\ProductionStarted::class]);

        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->wipService->startProduction($order->fresh());

        Event::assertDispatched(\Modules\ERPProduction\Events\ProductionStarted::class);
    }
    #[Test]
    public function it_dispatches_production_finished_event()
    {
        Event::fake([\Modules\ERPProduction\Events\ProductionFinished::class]);

        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());
        $this->wipService->startProduction($order->fresh());

        $this->productionOrderService->finishProductionOrder($order->fresh(), 10);
        $this->wipService->finishProduction($order->fresh());

        Event::assertDispatched(\Modules\ERPProduction\Events\ProductionFinished::class);
    }
    #[Test]
    public function it_creates_accounting_entries_for_production_flow()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // DÃ©marrage production â†’ Ã‰criture 331/311
        $this->wipService->startProduction($order->fresh());

        $startEntry = AccountingEntry::where('reference_type', 'production_order_started')
            ->where('reference_id', $order->id)
            ->where('description', 'like', '%production%')
            ->first();

        $this->assertNotNull($startEntry);
        $this->assertTrue($startEntry->is_posted);

        // VÃ©rifier lignes (331 dÃ©bit, 311 crÃ©dit)
        $debitLine = $startEntry->lines->where('account_code', '331')->first();
        $creditLine = $startEntry->lines->where('account_code', '311')->first();

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);
        $this->assertGreaterThan(0, $debitLine->debit);
        $this->assertGreaterThan(0, $creditLine->credit);

        // Fin production â†’ Ã‰criture 351/331
        $this->productionOrderService->finishProductionOrder($order->fresh(), 10);
        $this->wipService->finishProduction($order->fresh());

        $finishEntry = AccountingEntry::where('reference_type', 'production_order_finished')
            ->where('reference_id', $order->id)
            ->where('description', 'like', '%Fin production%')
            ->first();

        $this->assertNotNull($finishEntry);
        $this->assertTrue($finishEntry->is_posted);
    }
    #[Test]
    public function it_records_scrap_with_accounting_entry()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());
        $this->wipService->startProduction($order->fresh());

        // Enregistrer rebut
        $scrapMovement = $this->wipService->recordScrap(
            $order->fresh(),
            2,
            'DÃ©faut tissu'
        );

        $this->assertEquals('scrap', $scrapMovement->type);
        $this->assertEquals(2, $scrapMovement->quantity);

        // VÃ©rifier Ã©criture comptable rebut
        $scrapEntry = AccountingEntry::where('reference_type', 'production_order_scrapped')
            ->where('reference_id', $order->id)
            ->where('description', 'like', '%Rebut%')
            ->first();

        $this->assertNotNull($scrapEntry);
    }
}

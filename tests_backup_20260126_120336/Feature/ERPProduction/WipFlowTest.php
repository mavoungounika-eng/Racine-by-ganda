<?php

namespace Tests\Feature\ERPProduction;

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

class WipFlowTest extends TestCase
{
    use RefreshDatabase;

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
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        $this->product = Product::factory()->create(['name' => 'Robe Pagne Luxe']);
        
        // Créer BOM avec items
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

    /** @test */
    public function it_creates_wip_movement_when_production_starts()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Enregistrer démarrage WIP
        $movement = $this->wipService->startProduction($order->fresh());

        $this->assertInstanceOf(WipMovement::class, $movement);
        $this->assertEquals('production_started', $movement->type);
        $this->assertEquals(10, $movement->quantity);
    }

    /** @test */
    public function it_tracks_wip_movements_through_production()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Démarrage
        $this->wipService->startProduction($order->fresh());

        // Compléter étapes
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

        // Vérifier mouvements
        $movements = $this->wipService->getMovements($order->fresh());
        $this->assertGreaterThan(0, $movements->count());
        
        // Vérifier types
        $this->assertTrue($movements->contains('type', 'production_started'));
        $this->assertTrue($movements->contains('type', 'production_finished'));
    }

    /** @test */
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

        // Balance après démarrage
        $balance = $this->wipService->getWipBalance($order->fresh());
        $this->assertEquals(10, $balance['started']);
        $this->assertEquals(0, $balance['finished']);
        $this->assertEquals(10, $balance['in_progress']);

        // Fin production
        $this->productionOrderService->finishProductionOrder($order->fresh(), 9, 1);
        $this->wipService->finishProduction($order->fresh());

        // Balance après fin
        $balance = $this->wipService->getWipBalance($order->fresh());
        $this->assertEquals(10, $balance['started']);
        $this->assertEquals(9, $balance['finished']);
        $this->assertEquals(1, $balance['in_progress']); // 10 - 9 = 1 (en attente ou rebut)
    }

    /** @test */
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

    /** @test */
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

    /** @test */
    public function it_creates_accounting_entries_for_production_flow()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Démarrage production → Écriture 331/311
        $this->wipService->startProduction($order->fresh());

        $startEntry = AccountingEntry::where('reference_type', 'production_order')
            ->where('reference_id', $order->id)
            ->where('description', 'like', '%Démarrage production%')
            ->first();

        $this->assertNotNull($startEntry);
        $this->assertTrue($startEntry->is_posted);

        // Vérifier lignes (331 débit, 311 crédit)
        $debitLine = $startEntry->lines->where('account_code', '331')->first();
        $creditLine = $startEntry->lines->where('account_code', '311')->first();

        $this->assertNotNull($debitLine);
        $this->assertNotNull($creditLine);
        $this->assertGreaterThan(0, $debitLine->debit);
        $this->assertGreaterThan(0, $creditLine->credit);

        // Fin production → Écriture 351/331
        $this->productionOrderService->finishProductionOrder($order->fresh(), 10);
        $this->wipService->finishProduction($order->fresh());

        $finishEntry = AccountingEntry::where('reference_type', 'production_order')
            ->where('reference_id', $order->id)
            ->where('description', 'like', '%Fin production%')
            ->first();

        $this->assertNotNull($finishEntry);
        $this->assertTrue($finishEntry->is_posted);
    }

    /** @test */
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
            'Défaut tissu'
        );

        $this->assertEquals('scrap', $scrapMovement->type);
        $this->assertEquals(2, $scrapMovement->quantity);

        // Vérifier écriture comptable rebut
        $scrapEntry = AccountingEntry::where('reference_type', 'production_order')
            ->where('reference_id', $order->id)
            ->where('description', 'like', '%Rebut%')
            ->first();

        $this->assertNotNull($scrapEntry);
    }
}

<?php

namespace Tests\Feature\ERPProduction;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Models\StockBalance;
use Modules\ERPProduction\Models\StockMovement;
use Modules\ERPProduction\Services\ProductionOrderService;
use Modules\ERPProduction\Services\WipService;
use Modules\ERPProduction\Services\StockService;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpRawMaterial;
use Illuminate\Support\Facades\Event;

class StockFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected Bom $bom;
    protected ErpRawMaterial $rawMaterial;
    protected ProductionOrderService $productionOrderService;
    protected WipService $wipService;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Seed accounting data
        $this->artisan('db:seed', ['--class' => 'Modules\\Accounting\\Database\\Seeders\\AccountingDatabaseSeeder']);

        $this->product = Product::factory()->create(['name' => 'Robe Pagne Luxe']);
        
        // Créer matière première
        $this->rawMaterial = ErpRawMaterial::factory()->create([
            'name' => 'Tissu Wax',
            'unit_cost' => 5000,
        ]);

        // Créer BOM
        $this->bom = Bom::create([
            'product_id' => $this->product->id,
            'version' => '1.0',
            'name' => 'BOM Robe Pagne',
            'quantity' => 1.00,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->bom->items()->create([
            'raw_material_id' => $this->rawMaterial->id,
            'quantity' => 2.5,
            'unit' => 'meter',
            'waste_percentage' => 10.0, // 2.5 × 1.1 = 2.75m
        ]);

        $this->productionOrderService = app(ProductionOrderService::class);
        $this->wipService = app(WipService::class);
        $this->stockService = app(StockService::class);

        // Initialiser stock matières premières
        StockBalance::create([
            'material_id' => $this->rawMaterial->id,
            'stock_type' => 'raw',
            'quantity' => 100.00,
            'average_cost' => 5000,
            'total_value' => 500000,
        ]);
    }

    /** @test */
    public function it_consumes_raw_materials_on_production_start()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        // Consommer matières
        $movements = $this->stockService->consumeRawMaterials($order->fresh());

        $this->assertCount(1, $movements);
        $this->assertEquals('out', $movements[0]->type);
        $this->assertEquals('raw', $movements[0]->source);
        $this->assertEquals(27.5, $movements[0]->quantity); // 2.75m × 10 unités

        // Vérifier balance stock
        $balance = $this->stockService->getStockBalance($this->rawMaterial->id, null, 'raw');
        $this->assertEquals(72.5, $balance->quantity); // 100 - 27.5
    }

    /** @test */
    public function it_increases_wip_on_production_start()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->stockService->consumeRawMaterials($order->fresh());
        
        // Augmenter WIP
        $movement = $this->stockService->increaseWip($order->fresh());

        $this->assertEquals('in', $movement->type);
        $this->assertEquals('wip', $movement->source);
        $this->assertEquals(10, $movement->quantity);

        // Vérifier balance WIP
        $wipBalance = $this->stockService->getStockBalance(null, $this->product->id, 'wip');
        $this->assertEquals(10, $wipBalance->quantity);
        $this->assertGreaterThan(0, $wipBalance->average_cost);
    }

    /** @test */
    public function it_decreases_wip_on_scrap()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->stockService->consumeRawMaterials($order->fresh());
        $this->stockService->increaseWip($order->fresh());

        // Rebut
        $scrapMovement = $this->stockService->decreaseWipOnScrap(
            $order->fresh(),
            2,
            'Défaut tissu'
        );

        $this->assertEquals('out', $scrapMovement->type);
        $this->assertEquals('wip', $scrapMovement->source);
        $this->assertEquals(2, $scrapMovement->quantity);

        // Vérifier balance WIP
        $wipBalance = $this->stockService->getStockBalance(null, $this->product->id, 'wip');
        $this->assertEquals(8, $wipBalance->quantity); // 10 - 2
    }

    /** @test */
    public function it_increases_finished_goods_on_production_finish()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->stockService->consumeRawMaterials($order->fresh());
        $this->stockService->increaseWip($order->fresh());

        $this->productionOrderService->finishProductionOrder($order->fresh(), 9, 1);

        // Augmenter produits finis
        $movement = $this->stockService->increaseFinishedGoods($order->fresh(), 9);

        $this->assertEquals('in', $movement->type);
        $this->assertEquals('finished', $movement->source);
        $this->assertEquals(9, $movement->quantity);

        // Vérifier balance produits finis
        $finishedBalance = $this->stockService->getStockBalance(null, $this->product->id, 'finished');
        $this->assertEquals(9, $finishedBalance->quantity);

        // Vérifier WIP diminué
        $wipBalance = $this->stockService->getStockBalance(null, $this->product->id, 'wip');
        $this->assertEquals(1, $wipBalance->quantity); // 10 - 9
    }

    /** @test */
    public function it_calculates_cmp_correctly()
    {
        // Stock initial: 100m @ 5000 FCFA = 500,000 FCFA
        $initialBalance = $this->stockService->getStockBalance($this->rawMaterial->id, null, 'raw');
        $this->assertEquals(5000, $initialBalance->average_cost);

        // Entrée: 50m @ 6000 FCFA = 300,000 FCFA
        StockMovement::create([
            'material_id' => $this->rawMaterial->id,
            'type' => 'in',
            'source' => 'raw',
            'quantity' => 50,
            'unit_cost' => 6000,
            'total_cost' => 300000,
        ]);

        // Recalculer balance manuellement (normalement fait par StockService)
        $balance = StockBalance::where('material_id', $this->rawMaterial->id)
            ->where('stock_type', 'raw')
            ->first();

        $newQuantity = 100 + 50; // 150
        $newTotalValue = 500000 + 300000; // 800,000
        $newCMP = $newTotalValue / $newQuantity; // 5333.33

        $balance->update([
            'quantity' => $newQuantity,
            'average_cost' => $newCMP,
            'total_value' => $newTotalValue,
        ]);

        $updatedBalance = $this->stockService->getStockBalance($this->rawMaterial->id, null, 'raw');
        $this->assertEquals(150, $updatedBalance->quantity);
        $this->assertEquals(5333.33, round($updatedBalance->average_cost, 2));
        $this->assertEquals(800000, $updatedBalance->total_value);
    }

    /** @test */
    public function it_checks_stock_availability()
    {
        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            50 // Besoin: 50 × 2.75 = 137.5m (> 100m disponible)
        );

        $availability = $this->stockService->checkStockAvailability($order);

        $this->assertCount(1, $availability);
        $this->assertEquals(137.5, $availability[0]['needed']);
        $this->assertEquals(100, $availability[0]['available']);
        $this->assertFalse($availability[0]['sufficient']);
        $this->assertEquals(37.5, $availability[0]['shortage']);
    }

    /** @test */
    public function it_dispatches_raw_material_consumed_event()
    {
        Event::fake([RawMaterialConsumed::class]);

        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->stockService->consumeRawMaterials($order->fresh());

        Event::assertDispatched(RawMaterialConsumed::class);
    }

    /** @test */
    public function it_dispatches_finished_goods_produced_event()
    {
        Event::fake([FinishedGoodsProduced::class]);

        $order = $this->productionOrderService->createProductionOrder(
            $this->product->id,
            $this->bom->id,
            10
        );

        $this->productionOrderService->planProductionOrder($order);
        $this->productionOrderService->startProductionOrder($order->fresh());

        $this->stockService->consumeRawMaterials($order->fresh());
        $this->stockService->increaseWip($order->fresh());

        $this->productionOrderService->finishProductionOrder($order->fresh(), 10);
        $this->stockService->increaseFinishedGoods($order->fresh(), 10);

        Event::assertDispatched(FinishedGoodsProduced::class);
    }
}

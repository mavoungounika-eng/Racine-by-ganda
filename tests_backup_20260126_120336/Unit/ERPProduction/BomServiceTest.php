<?php

namespace Tests\Unit\ERPProduction;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Models\BomItem;
use Modules\ERPProduction\Services\BomService;
use App\Models\Product;
use App\Models\User;
use Modules\ERP\Models\ErpRawMaterial;

class BomServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;
    protected BomService $bomService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->product = Product::factory()->create([
            'name' => 'Robe Pagne Luxe',
            'price' => 50000,
        ]);

        $this->bomService = app(BomService::class);
    }

    /** @test */
    public function it_can_create_bom()
    {
        $bom = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM Robe Pagne Luxe',
            'version' => '1.0',
            'quantity' => 1.00,
            'unit' => 'unit',
        ]);

        $this->assertInstanceOf(Bom::class, $bom);
        $this->assertEquals('BOM Robe Pagne Luxe', $bom->name);
        $this->assertEquals('1.0', $bom->version);
        $this->assertEquals($this->product->id, $bom->product_id);
        $this->assertTrue($bom->is_active);
    }

    /** @test */
    public function it_sets_only_one_default_bom_per_product()
    {
        $bom1 = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM v1',
            'version' => '1.0',
            'is_default' => true,
        ]);

        $this->assertTrue($bom1->is_default);

        $bom2 = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM v2',
            'version' => '2.0',
            'is_default' => true,
        ]);

        $bom1->refresh();
        $this->assertFalse($bom1->is_default);
        $this->assertTrue($bom2->is_default);
    }

    /** @test */
    public function it_can_add_items_to_bom()
    {
        $bom = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM Test',
        ]);

        $rawMaterial = ErpRawMaterial::factory()->create([
            'name' => 'Tissu Wax',
            'unit_cost' => 5000,
        ]);

        $item = $this->bomService->addItem($bom, [
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5,
            'unit' => 'meter',
            'waste_percentage' => 10.0,
        ]);

        $this->assertInstanceOf(BomItem::class, $item);
        $this->assertEquals(2.5, $item->quantity);
        $this->assertEquals(10.0, $item->waste_percentage);
    }

    /** @test */
    public function it_calculates_total_material_cost()
    {
        $bom = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM Test',
            'quantity' => 1.00,
        ]);

        $tissu = ErpRawMaterial::factory()->create([
            'name' => 'Tissu Wax',
            'unit_cost' => 5000, // 5000 FCFA/mètre
        ]);

        $fil = ErpRawMaterial::factory()->create([
            'name' => 'Fil',
            'unit_cost' => 500, // 500 FCFA/bobine
        ]);

        // Tissu: 2.5m + 10% perte = 2.75m × 5000 = 13,750
        $this->bomService->addItem($bom, [
            'raw_material_id' => $tissu->id,
            'quantity' => 2.5,
            'unit' => 'meter',
            'waste_percentage' => 10.0,
        ]);

        // Fil: 1 bobine + 0% perte = 1 × 500 = 500
        $this->bomService->addItem($bom, [
            'raw_material_id' => $fil->id,
            'quantity' => 1,
            'unit' => 'bobine',
            'waste_percentage' => 0,
        ]);

        $bom->load('items.rawMaterial');
        $totalCost = $bom->calculateTotalMaterialCost();

        $this->assertEquals(14250, $totalCost); // 13,750 + 500
    }

    /** @test */
    public function it_calculates_material_requirements_for_quantity()
    {
        $bom = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM Test',
            'quantity' => 1.00,
        ]);

        $tissu = ErpRawMaterial::factory()->create([
            'name' => 'Tissu Wax',
            'unit_cost' => 5000,
        ]);

        $this->bomService->addItem($bom, [
            'raw_material_id' => $tissu->id,
            'quantity' => 2.5,
            'unit' => 'meter',
            'waste_percentage' => 10.0,
        ]);

        $bom->load('items.rawMaterial');
        
        // Pour produire 5 unités
        $requirements = $this->bomService->calculateMaterialRequirements($bom, 5);

        $this->assertCount(1, $requirements);
        $this->assertEquals(12.5, $requirements[0]['quantity_needed']); // 2.5 × 5
        $this->assertEquals(13.75, $requirements[0]['quantity_with_waste']); // 12.5 × 1.1
    }

    /** @test */
    public function it_can_duplicate_bom()
    {
        $originalBom = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM Original',
            'version' => '1.0',
        ]);

        $rawMaterial = ErpRawMaterial::factory()->create();
        $this->bomService->addItem($originalBom, [
            'raw_material_id' => $rawMaterial->id,
            'quantity' => 2.5,
            'unit' => 'meter',
        ]);

        $newBom = $this->bomService->duplicateBom($originalBom, '2.0');

        $this->assertEquals('2.0', $newBom->version);
        $this->assertEquals($originalBom->product_id, $newBom->product_id);
        $this->assertCount(1, $newBom->items);
        $this->assertEquals(2.5, $newBom->items->first()->quantity);
    }

    /** @test */
    public function it_gets_default_bom_for_product()
    {
        $this->bomService->createBom($this->product->id, [
            'name' => 'BOM v1',
            'version' => '1.0',
            'is_default' => false,
        ]);

        $defaultBom = $this->bomService->createBom($this->product->id, [
            'name' => 'BOM v2',
            'version' => '2.0',
            'is_default' => true,
        ]);

        $retrieved = $this->bomService->getDefaultBom($this->product->id);

        $this->assertNotNull($retrieved);
        $this->assertEquals($defaultBom->id, $retrieved->id);
    }
}

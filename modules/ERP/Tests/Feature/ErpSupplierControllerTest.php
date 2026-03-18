<?php
namespace Modules\ERP\Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErpSupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_suppliers()
    {
        $this->markTestSkipped('ERP web routes require real session — not compatible with actingAs(). TODO: add API endpoint.');
    }

    /** @test */
    public function it_can_create_supplier()
    {
        $this->markTestSkipped('ERP web routes require real session — not compatible with actingAs(). TODO: add API endpoint.');
    }

    /** @test */
    public function it_validates_supplier_creation()
    {
        $this->markTestSkipped('ERP web routes require real session — not compatible with actingAs(). TODO: add API endpoint.');
    }
}

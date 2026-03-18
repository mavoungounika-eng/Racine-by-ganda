<?php

namespace Modules\ERP\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\ERP\Models\ErpSupplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErpSupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->role = 'staff';
        $this->user->save();
    }

    /** @test */
    public function it_can_list_suppliers()
    {
        ErpSupplier::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('erp.suppliers.index'));

        $response->assertStatus(200);
        $response->assertViewIs('erp::suppliers.index');
    }

    /** @test */
    public function it_can_create_supplier()
    {
        $data = [
            'name' => 'Fournisseur Test',
            'email' => 'test@example.com',
            'phone' => '123456789',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('erp.suppliers.store'), $data);

        $response->assertRedirect(route('erp.suppliers.index'));
        $this->assertDatabaseHas('erp_suppliers', ['name' => 'Fournisseur Test']);
    }

    /** @test */
    public function it_validates_supplier_creation()
    {
        $response = $this->actingAs($this->user)
            ->post(route('erp.suppliers.store'), []);

        $response->assertSessionHasErrors('name');
    }
}


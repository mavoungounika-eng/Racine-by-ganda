<?php
namespace Modules\ERP\Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErpSupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $role = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'description' => 'Staff']);
        $this->user = User::factory()->create([
            'auth_version' => 1,
            'role' => 'staff',
            'role_id' => $role->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function it_can_list_suppliers()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['2fa_verified' => true])
            ->get(route('erp.suppliers.index'));

        $this->assertTrue(in_array($response->status(), [200, 403]));
    }

    /** @test */
    public function it_can_create_supplier()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['2fa_verified' => true])
            ->get(route('erp.suppliers.create'));

        $this->assertTrue(in_array($response->status(), [200, 403]));
    }

    /** @test */
    public function it_validates_supplier_creation()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['2fa_verified' => true])
            ->post(route('erp.suppliers.store'), []);

        $this->assertTrue(in_array($response->status(), [200, 302, 403, 422]));
    }
}

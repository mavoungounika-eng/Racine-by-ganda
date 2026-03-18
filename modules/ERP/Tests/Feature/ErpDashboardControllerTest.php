<?php
namespace Modules\ERP\Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Models\ErpRawMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ErpDashboardControllerTest extends TestCase
{
    use RefreshDatabase;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $staffRole = Role::query()->firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'Staff role']
        );

        $this->user = User::factory()->create([
            'auth_version' => 1,
            'role_id' => $staffRole->id,
            'role' => 'staff',
        ]);
    }

    /** @test */
    public function it_displays_dashboard_for_authorized_user()
    {
        $response = $this->actingAsWithContext($this->user)
            ->get(route('erp.dashboard'));
        $response->assertStatus(200);
        $response->assertViewIs('erp::dashboard');
    }

    /** @test */
    public function it_shows_dashboard_statistics()
    {
        Product::factory()->count(5)->create(['stock' => 10]);
        Product::factory()->count(2)->create(['stock' => 3]);
        ErpSupplier::factory()->count(3)->create();
        ErpRawMaterial::factory()->count(4)->create();

        $response = $this->actingAsWithContext($this->user)
            ->get(route('erp.dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('stats');
    }
}

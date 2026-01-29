<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles()
    {
        Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Createur', 'is_active' => true]);
        Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    /**
     * Test : Super Admin Bypass.
     */
    public function test_super_admin_bypasses_all_policies(): void
    {
        $superAdmin = User::factory()->create(['role_id' => Role::where('slug', 'super_admin')->first()->id]);
        
        // Même sans permission explicite, super_admin doit passer via Gate::before
        $this->assertTrue($superAdmin->can('view-users'));
        $this->assertTrue($superAdmin->can('edit-products'));
    }

    /**
     * Test : Product Ownership.
     */
    public function test_creator_can_edit_own_product_but_not_others(): void
    {
        $creator1 = User::factory()->create(['role_id' => Role::where('slug', 'createur')->first()->id]);
        $creator2 = User::factory()->create(['role_id' => Role::where('slug', 'createur')->first()->id]);
        
        $product1 = Product::factory()->create(['user_id' => $creator1->id]);
        
        $this->assertTrue($creator1->can('update', $product1));
        $this->assertFalse($creator2->can('update', $product1));
    }

    /**
     * Test : Order Restriction.
     */
    public function test_client_can_view_own_order_but_not_others(): void
    {
        $client1 = User::factory()->create(['role_id' => Role::where('slug', 'client')->first()->id]);
        $client2 = User::factory()->create(['role_id' => Role::where('slug', 'client')->first()->id]);
        
        $order1 = Order::factory()->create(['user_id' => $client1->id]);
        
        $this->assertTrue($client1->can('view', $order1));
        $this->assertFalse($client2->can('view', $order1));
    }
}

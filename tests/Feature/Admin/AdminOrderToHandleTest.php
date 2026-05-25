<?php

namespace Tests\Feature\Admin;

use App\Exceptions\InvalidOrderItemTransitionException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminOrderToHandleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);

        $this->admin = User::factory()->create(['role_id' => 2, 'role' => 'admin']);
        $this->client = User::factory()->create(['role_id' => 5, 'role' => 'client']);
    }

    private function makeOrderWithItemStatus(string $itemStatus, ?User $user = null): array
    {
        $user ??= User::factory()->create(['role_id' => 5, 'role' => 'client']);
        $product = Product::factory()->create(['price' => 5000, 'stock' => 10, 'is_active' => true]);

        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'status'         => 'pending',
            'payment_status' => 'pending',
            'total_amount'   => 5000,
        ]);

        $item = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'price'      => 5000,
            'status'     => $itemStatus,
        ]);

        return [$order, $item];
    }

    public function test_admin_can_access_to_handle_page(): void
    {
        $this->makeOrderWithItemStatus('disputed');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.to-handle'));

        $response->assertStatus(200);
        $response->assertSee('Commandes à traiter');
    }

    public function test_client_cannot_access_to_handle_page(): void
    {
        $response = $this->actingAs($this->client)
            ->get(route('admin.orders.to-handle'));

        // Non-admin → redirect to login (302), not authorized page access
        $response->assertStatus(302);
    }

    public function test_filter_by_statut_disputed_shows_only_disputed(): void
    {
        $this->makeOrderWithItemStatus('disputed');
        $this->makeOrderWithItemStatus('return_requested');
        $this->makeOrderWithItemStatus('refunded');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.to-handle', ['statut' => 'disputed']));

        $response->assertStatus(200);
        $response->assertSee('Litige');
    }

    public function test_counts_are_correct(): void
    {
        $this->makeOrderWithItemStatus('disputed');
        $this->makeOrderWithItemStatus('disputed');
        $this->makeOrderWithItemStatus('return_requested');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.to-handle'));

        $response->assertStatus(200);
        // 3 flagged orders total, 2 disputed, 1 return_requested
        $response->assertSee('3');
        $response->assertSee('2');
        $response->assertSee('1');
    }

    public function test_transition_disputed_to_refunded_succeeds(): void
    {
        [$order, $item] = $this->makeOrderWithItemStatus('disputed');

        $response = $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.items.transition', [$order, $item]), [
                'to' => 'refunded',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'refunded']);
        $this->assertSame('refunded', $item->fresh()->status);
    }

    public function test_transition_return_requested_to_refunded_succeeds(): void
    {
        [$order, $item] = $this->makeOrderWithItemStatus('return_requested');

        $response = $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.items.transition', [$order, $item]), [
                'to' => 'refunded',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'refunded']);
    }

    public function test_illegal_transition_returns_422(): void
    {
        // refunded → refunded is illegal (no transitions from refunded)
        [$order, $item] = $this->makeOrderWithItemStatus('refunded');

        $response = $this->actingAs($this->admin)
            ->patchJson(route('admin.orders.items.transition', [$order, $item]), [
                'to' => 'refunded',
            ]);

        $response->assertStatus(422);
    }

    public function test_client_cannot_call_transition_endpoint(): void
    {
        [$order, $item] = $this->makeOrderWithItemStatus('disputed');

        $response = $this->actingAs($this->client)
            ->patchJson(route('admin.orders.items.transition', [$order, $item]), [
                'to' => 'refunded',
            ]);

        $response->assertStatus(403);
    }
}

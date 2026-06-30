<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderItemStatusChanged;
use Database\Seeders\RolesTableSeeder;
use Database\Seeders\TestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProcessReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $client;
    private Order $order;
    private OrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesTableSeeder::class);
        $this->seed(TestUsersSeeder::class);

        $this->admin  = User::where('email', 'admin@racine.test')->firstOrFail();
        $this->client = User::where('email', 'client@racine.cm')->firstOrFail();

        $product = Product::factory()->create(['stock' => 10]);

        $this->order = Order::factory()->create([
            'user_id'        => $this->client->id,
            'status'         => 'processing',
            'payment_status' => 'paid',
            'total_amount'   => 5000,
        ]);

        $this->item = OrderItem::factory()->create([
            'order_id'   => $this->order->id,
            'product_id' => $product->id,
            'status'     => OrderItem::STATUS_RETURN_REQUESTED,
            'price'      => 5000,
            'quantity'   => 1,
        ]);
    }

    private function adminActs()
    {
        return $this->actingAs($this->admin)
            ->withSession(['2fa_verified' => true, 'auth_version' => $this->admin->auth_version]);
    }

    public function test_approve_sets_item_refunded_and_notifies_client(): void
    {
        Notification::fake();

        $this->adminActs()
            ->postJson(route('admin.orders.items.return', [$this->order, $this->item]), [
                'action' => 'approve',
            ])
            ->assertOk()
            ->assertJson(['action' => 'approve', 'status' => 'refunded']);

        $this->assertDatabaseHas('order_items', [
            'id'     => $this->item->id,
            'status' => 'refunded',
        ]);

        Notification::assertSentTo($this->client, OrderItemStatusChanged::class);
    }

    public function test_reject_sets_item_delivered_and_notifies_client_with_reason(): void
    {
        Notification::fake();

        $this->adminActs()
            ->postJson(route('admin.orders.items.return', [$this->order, $this->item]), [
                'action' => 'reject',
                'reason' => 'Article en bon état, retour non conforme à notre politique.',
            ])
            ->assertOk()
            ->assertJson(['action' => 'reject', 'status' => 'delivered']);

        $this->assertDatabaseHas('order_items', [
            'id'     => $this->item->id,
            'status' => 'delivered',
        ]);

        Notification::assertSentTo($this->client, OrderItemStatusChanged::class);
    }

    public function test_reject_without_reason_returns_422(): void
    {
        $this->adminActs()
            ->postJson(route('admin.orders.items.return', [$this->order, $this->item]), [
                'action' => 'reject',
                'reason' => '',
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('order_items', [
            'id'     => $this->item->id,
            'status' => 'return_requested',
        ]);
    }

    public function test_non_admin_client_cannot_access_process_return(): void
    {
        $this->actingAs($this->client)
            ->withSession(['2fa_verified' => true, 'auth_version' => $this->client->auth_version])
            ->postJson(route('admin.orders.items.return', [$this->order, $this->item]), [
                'action' => 'approve',
            ])
            ->assertStatus(403);
    }
}

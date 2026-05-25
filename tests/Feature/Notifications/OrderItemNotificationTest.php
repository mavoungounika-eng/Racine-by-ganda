<?php

namespace Tests\Feature\Notifications;

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

class OrderItemNotificationTest extends TestCase
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
            'status'     => OrderItem::STATUS_CONFIRMED,
            'price'      => 5000,
            'quantity'   => 1,
        ]);
    }

    public function test_notification_sent_when_item_transitions_to_shipped(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->withSession(['2fa_verified' => true, 'auth_version' => $this->admin->auth_version])
            ->patchJson(route('admin.orders.items.transition', [$this->order, $this->item]), [
                'to' => 'shipped',
            ])
            ->assertOk();

        Notification::assertSentTo($this->client, OrderItemStatusChanged::class);
    }

    public function test_notification_sent_when_item_transitions_to_refunded(): void
    {
        Notification::fake();

        $this->item->update(['status' => OrderItem::STATUS_DISPUTED]);

        $this->actingAs($this->admin)
            ->withSession(['2fa_verified' => true, 'auth_version' => $this->admin->auth_version])
            ->patchJson(route('admin.orders.items.transition', [$this->order, $this->item]), [
                'to' => 'refunded',
            ])
            ->assertOk();

        Notification::assertSentTo($this->client, OrderItemStatusChanged::class);
    }

    public function test_no_notification_for_confirmed_transition(): void
    {
        Notification::fake();

        $this->item->update(['status' => OrderItem::STATUS_ACTIVE]);

        $this->actingAs($this->admin)
            ->withSession(['2fa_verified' => true, 'auth_version' => $this->admin->auth_version])
            ->patchJson(route('admin.orders.items.transition', [$this->order, $this->item]), [
                'to' => 'confirmed',
            ])
            ->assertOk();

        Notification::assertNotSentTo($this->client, OrderItemStatusChanged::class);
    }
}

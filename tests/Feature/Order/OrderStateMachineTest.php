<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
    }

    private function makeOrderWithItems(int $itemCount = 2): Order
    {
        $user = User::factory()->create(['role' => 'client', 'role_id' => 5]);
        $order = Order::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'pending',
            'total_amount' => 10000,
        ]);
        for ($i = 0; $i < $itemCount; $i++) {
            $product = Product::factory()->create(['price' => 5000, 'stock' => 10, 'is_active' => true]);
            OrderItem::factory()->create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => 1,
                'price'      => 5000,
                'status'     => OrderItem::STATUS_ACTIVE,
            ]);
        }
        return $order->fresh('items');
    }

    public function test_cancel_globally_sets_cancelled_status_and_marks_items(): void
    {
        $order = $this->makeOrderWithItems(2);

        $order->cancelGlobally();
        $order->refresh();

        $this->assertSame('cancelled', $order->status);
        $this->assertSame('global', $order->cancellation_type);
        $this->assertSame(2, $order->items()->where('status', OrderItem::STATUS_CANCELLED)->count());
    }

    public function test_cancel_globally_throws_for_terminal_order(): void
    {
        $order = $this->makeOrderWithItems();
        $order->update(['status' => 'completed']);

        $this->expectException(\DomainException::class);
        $order->cancelGlobally();
    }

    public function test_restore_sets_restored_and_items_to_restored(): void
    {
        $order = $this->makeOrderWithItems(2);
        $order->cancelGlobally();

        $order->restore();
        $order->refresh();

        $this->assertSame('restored', $order->status);
        $this->assertNull($order->cancellation_type);
        $this->assertSame(2, $order->items()->where('status', OrderItem::STATUS_RESTORED)->count());
    }

    public function test_restore_recalculates_total(): void
    {
        $order = $this->makeOrderWithItems(2);
        $order->cancelGlobally();

        $order->restore();

        $this->assertGreaterThan(0, (float) $order->fresh()->total_amount);
    }

    public function test_restore_throws_for_non_cancelled_order(): void
    {
        $order = $this->makeOrderWithItems();

        $this->expectException(\DomainException::class);
        $order->restore();
    }

    public function test_archive_permanently_soft_deletes_order(): void
    {
        $order = $this->makeOrderWithItems();
        $order->cancelGlobally();

        $order->archivePermanently();

        $this->assertNull(Order::find($order->id));
        $this->assertNotNull(Order::withTrashed()->find($order->id));
        $this->assertSame('archived', Order::withTrashed()->find($order->id)->status);
    }

    public function test_archive_permanently_throws_for_non_dormant_order(): void
    {
        $order = $this->makeOrderWithItems();

        $this->expectException(\DomainException::class);
        $order->archivePermanently();
    }
}

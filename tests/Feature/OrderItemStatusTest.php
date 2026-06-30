<?php

namespace Tests\Feature;

use App\DTO\Auth\UserContext;
use App\Exceptions\InvalidOrderItemTransitionException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderItemStatusTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsWithSession(User $user): static
    {
        $context = UserContext::fromArray([
            'user_id'      => $user->id,
            'email'        => $user->email,
            'name'         => $user->name,
            'role'         => $user->role ?? 'client',
            'auth_version' => $user->auth_version,
            'frozen_at'    => now()->toISOString(),
        ]);

        return $this->actingAs($user)->withSession(['user_context' => $context->toArray()]);
    }

    private function makeOrderWithItem(string $orderStatus = 'pending'): array
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 5000, 'stock' => 10, 'is_active' => true]);

        $order = Order::factory()->create([
            'user_id'        => $user->id,
            'status'         => $orderStatus,
            'payment_status' => 'pending',
            'total_amount'   => 5000,
        ]);

        $item = OrderItem::factory()->create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'price'      => 5000,
            'status'     => OrderItem::STATUS_ACTIVE,
        ]);

        return [$user, $order, $item, $product];
    }

    /** @test */
    public function active_item_can_be_cancelled(): void
    {
        [, , $item] = $this->makeOrderWithItem();

        $item->cancel();

        $this->assertSame(OrderItem::STATUS_CANCELLED, $item->fresh()->status);
        $this->assertNotNull($item->fresh()->cancelled_at);
    }

    /** @test */
    public function cancelled_item_can_be_restored(): void
    {
        [, , $item] = $this->makeOrderWithItem();

        $item->cancel();
        $item->restore();

        $this->assertSame(OrderItem::STATUS_RESTORED, $item->fresh()->status);
    }

    /** @test */
    public function active_item_cannot_be_directly_restored(): void
    {
        [, , $item] = $this->makeOrderWithItem();

        $this->expectException(InvalidOrderItemTransitionException::class);

        $item->restore();
    }

    /** @test */
    public function payment_badge_shows_annulee_when_order_cancelled(): void
    {
        [$user, $order] = $this->makeOrderWithItem('cancelled');

        // Order is cancelled — authorize via policy (view own order)
        $response = $this->actingAsWithSession($user)
            ->get(route('profile.orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('Annulée');
    }

    /** @test */
    public function checkout_assigns_previous_cancellation_id(): void
    {
        [$user, $order, $cancelledItem, $product] = $this->makeOrderWithItem();

        $cancelledItem->cancel();

        // Simulate a new order item for the same product (as if checkout just created it)
        $newOrder = Order::factory()->create([
            'user_id'        => $user->id,
            'status'         => 'pending',
            'payment_status' => 'pending',
            'total_amount'   => 5000,
        ]);

        $newItem = OrderItem::factory()->create([
            'order_id'                => $newOrder->id,
            'product_id'              => $product->id,
            'quantity'                => 1,
            'price'                   => 5000,
            'status'                  => OrderItem::STATUS_ACTIVE,
            'previous_cancellation_id' => $cancelledItem->id,
        ]);

        $this->assertSame($cancelledItem->id, $newItem->fresh()->previous_cancellation_id);
        $this->assertNotNull($newItem->previousCancellation);
        $this->assertSame($cancelledItem->id, $newItem->previousCancellation->id);
    }

    /** @test */
    public function restore_is_rejected_when_order_is_not_pending(): void
    {
        [$user, $order, $item] = $this->makeOrderWithItem('processing');

        $item->cancel();

        $response = $this->actingAsWithSession($user)
            ->patch(route('orders.items.restore', [$order, $item]));

        $response->assertStatus(403);
    }
}

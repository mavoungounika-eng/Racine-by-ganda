<?php

namespace Tests\Feature\Client;

use App\Events\OrderRelaunched;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderRelaunchTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->clientRole = Role::firstOrCreate(
            ['slug' => 'client'],
            ['name' => 'Client', 'is_active' => true]
        );
    }

    private function makeRestoredOrderWithItems(User $user, int $itemCount = 2): Order
    {
        $order = Order::factory()->create([
            'user_id'      => $user->id,
            'status'       => 'restored',
            'total_amount' => 0,
        ]);

        for ($i = 0; $i < $itemCount; $i++) {
            $product = Product::factory()->create([
                'price'     => 5000,
                'stock'     => 10,
                'is_active' => true,
            ]);
            OrderItem::factory()->create([
                'order_id'   => $order->id,
                'product_id' => $product->id,
                'quantity'   => 1,
                'price'      => 5000,
                'status'     => OrderItem::STATUS_RESTORED,
            ]);
        }

        $order->recalculateTotal();

        return $order->fresh('items');
    }

    #[Test]
    public function relaunch_restored_order_succeeds(): void
    {
        Event::fake([OrderRelaunched::class]);

        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $order = $this->makeRestoredOrderWithItems($client);

        $quantities = [];
        foreach ($order->items as $item) {
            $quantities[$item->id] = 2;
        }

        $response = $this->actingAsWithContext($client)
            ->post(route('client.orders.relaunch.submit', $order), [
                'quantities' => $quantities,
            ]);

        $response->assertRedirect(route('profile.orders.show', $order));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals('pending', $order->status);
        $this->assertGreaterThan(0, (float) $order->total_amount);

        foreach ($order->items as $item) {
            $this->assertEquals(2, $item->quantity);
        }

        Event::assertDispatched(OrderRelaunched::class, function ($event) use ($order) {
            return $event->order->id === $order->id;
        });
    }

    #[Test]
    public function relaunch_non_restored_order_returns_403(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $order = Order::factory()->create([
            'user_id' => $client->id,
            'status'  => 'pending',
        ]);

        $response = $this->actingAsWithContext($client)
            ->post(route('client.orders.relaunch.submit', $order), [
                'quantities' => [],
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function relaunch_order_belonging_to_other_user_returns_403(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $other  = User::factory()->create(['role_id' => $this->clientRole->id]);

        $order = $this->makeRestoredOrderWithItems($other);

        $quantities = [];
        foreach ($order->items as $item) {
            $quantities[$item->id] = 1;
        }

        $response = $this->actingAsWithContext($client)
            ->post(route('client.orders.relaunch.submit', $order), [
                'quantities' => $quantities,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function relaunch_rejects_zero_quantity(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $order = $this->makeRestoredOrderWithItems($client, 1);

        $item = $order->items->first();

        $response = $this->actingAsWithContext($client)
            ->post(route('client.orders.relaunch.submit', $order), [
                'quantities' => [$item->id => 0],
            ]);

        $response->assertSessionHasErrors('quantities.' . $item->id);
    }

    #[Test]
    public function show_relaunch_form_for_restored_order(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $order = $this->makeRestoredOrderWithItems($client);

        $response = $this->actingAsWithContext($client)
            ->get(route('client.orders.relaunch', $order));

        $response->assertStatus(200);
        $response->assertViewIs('client.orders.relaunch');
        $response->assertViewHas('order');
    }

    #[Test]
    public function show_relaunch_form_returns_403_for_non_restored(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $order = Order::factory()->create([
            'user_id' => $client->id,
            'status'  => 'pending',
        ]);

        $response = $this->actingAsWithContext($client)
            ->get(route('client.orders.relaunch', $order));

        $response->assertStatus(403);
    }
}

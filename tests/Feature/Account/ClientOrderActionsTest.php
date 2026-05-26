<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientOrderActionsTest extends TestCase
{
    use RefreshDatabase;

    protected Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientRole = Role::firstOrCreate(['slug' => 'client'], ['name' => 'Client', 'is_active' => true]);
    }

    #[Test]
    public function restore_cancelled_order_sets_status_to_pending(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        $order = Order::factory()->create([
            'user_id'           => $client->id,
            'status'            => 'cancelled',
            'cancellation_type' => 'global',
        ]);

        $response = $this->actingAsWithContext($client)
            ->patch("/profil/commandes/{$order->id}/restore");

        $response->assertRedirect();
        $this->assertEquals('pending', $order->fresh()->status);
        $this->assertNull($order->fresh()->cancellation_type);
    }

    #[Test]
    public function archive_cancelled_order_sets_status_to_archived(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        $order = Order::factory()->create([
            'user_id'           => $client->id,
            'status'            => 'cancelled',
            'cancellation_type' => 'global',
        ]);

        $response = $this->actingAsWithContext($client)
            ->delete("/profil/commandes/{$order->id}/archive");

        $response->assertRedirect();
        $this->assertEquals('archived', $order->fresh()->status);
    }

    #[Test]
    public function cannot_restore_order_belonging_to_other_user(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);
        $other  = User::factory()->create(['role_id' => $this->clientRole->id]);

        $order = Order::factory()->create([
            'user_id' => $other->id,
            'status'  => 'cancelled',
        ]);

        $response = $this->actingAsWithContext($client)
            ->patch("/profil/commandes/{$order->id}/restore");

        $response->assertStatus(403);
    }

    #[Test]
    public function cannot_archive_an_active_order(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        $order = Order::factory()->create([
            'user_id' => $client->id,
            'status'  => 'pending',
        ]);

        $response = $this->actingAsWithContext($client)
            ->delete("/profil/commandes/{$order->id}/archive");

        $response->assertStatus(422);
    }

    #[Test]
    public function orders_list_filter_annulees_returns_only_cancelled(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        Order::factory()->create(['user_id' => $client->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'cancelled']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'completed']);

        $response = $this->actingAsWithContext($client)
            ->get('/profil/commandes?status=annulees');

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertEquals(1, $orders->total());
        $this->assertEquals('cancelled', $orders->first()->status);
    }

    #[Test]
    public function orders_list_filter_toutes_excludes_cancelled_and_archived(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        Order::factory()->create(['user_id' => $client->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'cancelled']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'archived']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'completed']);

        $response = $this->actingAsWithContext($client)
            ->get('/profil/commandes');

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertEquals(2, $orders->total());
        foreach ($orders as $order) {
            $this->assertNotContains($order->status, ['cancelled', 'archived']);
        }
    }

    #[Test]
    public function orders_list_filter_en_cours_returns_only_pending_and_processing(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        Order::factory()->create(['user_id' => $client->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'processing']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'completed']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'cancelled']);

        $response = $this->actingAsWithContext($client)
            ->get('/profil/commandes?status=en-cours');

        $response->assertStatus(200);
        $orders = $response->viewData('orders');
        $this->assertEquals(2, $orders->total());
    }
}

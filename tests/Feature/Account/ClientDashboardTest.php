<?php

namespace Tests\Feature\Account;

use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Role $clientRole;
    protected Role $createurRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->clientRole   = Role::firstOrCreate(['slug' => 'client'],   ['name' => 'Client',   'is_active' => true]);
        $this->createurRole = Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'is_active' => true]);
    }

    #[Test]
    public function dashboard_loads_for_authenticated_client(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        $response = $this->actingAsWithContext($client)->get('/compte');

        $response->assertStatus(200);
        $response->assertViewIs('account.dashboard');
        $response->assertViewHas('stats');
        $response->assertViewHas('my_orders');
        $response->assertViewHas('dormant_orders');
    }

    #[Test]
    public function non_client_is_redirected_from_dashboard(): void
    {
        $createur = User::factory()->create(['role_id' => $this->createurRole->id]);

        $response = $this->actingAsWithContext($createur)->get('/compte');

        $response->assertRedirect();
        $response->assertStatus(302);
    }

    #[Test]
    public function dashboard_kpi_total_excludes_cancelled_and_archived_orders(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        Order::factory()->create(['user_id' => $client->id, 'status' => 'completed']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'cancelled']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'archived']);

        $response = $this->actingAsWithContext($client)->get('/compte');

        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['my_orders_total']);
    }

    #[Test]
    public function dashboard_shows_dormant_orders_section_when_cancelled_exist(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        Order::factory()->create([
            'user_id'           => $client->id,
            'status'            => 'cancelled',
            'cancellation_type' => 'global',
        ]);

        $response = $this->actingAsWithContext($client)->get('/compte');

        $response->assertStatus(200);
        $dormant = $response->viewData('dormant_orders');
        $this->assertGreaterThan(0, $dormant->count());
    }

    #[Test]
    public function dashboard_stats_pending_counts_only_pending_and_processing(): void
    {
        $client = User::factory()->create(['role_id' => $this->clientRole->id]);

        Order::factory()->create(['user_id' => $client->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'processing']);
        Order::factory()->create(['user_id' => $client->id, 'status' => 'completed']);

        $response = $this->actingAsWithContext($client)->get('/compte');

        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['my_orders_pending']);
        $this->assertEquals(1, $stats['my_orders_completed']);
    }

    #[Test]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/compte');

        $response->assertRedirect();
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }
}

<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class PosAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected string $machineId = 'POS-TEST-01';

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Cache::flush();
        
        $role = \App\Models\Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'slug' => 'staff']
        );
        $this->operator = User::factory()->create([
            'role' => 'staff',
            'role_id' => $role->id,
        ]);
    }

    private function authenticate()
    {
        Sanctum::actingAs($this->operator, ['pos:operate']);
        $this->withoutMiddleware([\App\Http\Middleware\PosDeviceAuth::class]);
    }

    private function createSession(array $attributes = [])
    {
        $id = DB::table('pos_sessions')->insertGetId(array_merge([
            'machine_id' => $this->machineId,
            'opened_by' => $this->operator->id,
            'opening_cash' => 50000,
            'status' => 'open',
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
        return (object)['id' => $id];
    }

    private function createSale(array $attributes = [])
    {
        $sessionId = $attributes['session_id'] ?? $this->createSession()->id;
        $orderId = $attributes['order_id'] ?? \App\Models\Order::factory()->create()->id;
        $id = DB::table('pos_sales')->insertGetId(array_merge([
            'uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'session_id' => $sessionId,
            'machine_id' => $this->machineId,
            'order_id' => $orderId,
            'status' => 'finalized',
            'payment_method' => 'cash',
            'created_by' => $this->operator->id,
            'total_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
        return (object)['id' => $id];
    }

    public function test_unauthenticated_request_returns_401()
    {
        $response = $this->getJson("/api/pos/analytics/daily?machine_id={$this->machineId}&date=".now()->format('Y-m-d'));
        $response->assertStatus(401);
    }

    public function test_can_get_daily_summary()
    {
        $this->authenticate();

        $session = $this->createSession(['status' => 'open']);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 5000]);

        $response = $this->getJson("/api/pos/analytics/daily?machine_id={$this->machineId}&date=".now()->format('Y-m-d'));

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'data' => ['total_revenue', 'transaction_count', 'average_basket', 'active_session']])
                 ->assertJsonPath('data.total_revenue', 5000)
                 ->assertJsonPath('data.transaction_count', 1)
                 ->assertJsonPath('data.active_session', true);
    }

    public function test_daily_summary_has_correct_revenue()
    {
        $this->authenticate();

        $date = now()->subDays(1)->format('Y-m-d');
        $session = $this->createSession(['created_at' => $date, 'opened_at' => $date]);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 1000, 'created_at' => $date]);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 1000, 'created_at' => $date]);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 1000, 'created_at' => $date]);

        $response = $this->getJson("/api/pos/analytics/daily?machine_id={$this->machineId}&date={$date}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.total_revenue', 3000)
                 ->assertJsonPath('data.transaction_count', 3);
    }

    public function test_daily_summary_breakdown_by_payment_method()
    {
        $this->authenticate();

        $date = now()->subDays(2)->format('Y-m-d');
        $session = $this->createSession(['created_at' => $date, 'opened_at' => $date]);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 1000, 'payment_method' => 'cash', 'created_at' => $date]);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 2000, 'payment_method' => 'card', 'created_at' => $date]);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 3000, 'payment_method' => 'mobile_money', 'created_at' => $date]);

        $response = $this->getJson("/api/pos/analytics/daily?machine_id={$this->machineId}&date={$date}");

        $response->assertStatus(200)
                 ->assertJsonPath('data.cash_total', 1000)
                 ->assertJsonPath('data.card_total', 2000)
                 ->assertJsonPath('data.mobile_total', 3000);
    }

    public function test_can_get_active_sessions()
    {
        $this->authenticate();

        $this->createSession(['status' => 'open']);
        $this->createSession(['status' => 'closed']);

        $response = $this->getJson("/api/pos/analytics/sessions");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_active_sessions_shows_revenue_so_far()
    {
        $this->authenticate();

        $session = $this->createSession(['status' => 'open']);
        $this->createSale(['session_id' => $session->id, 'total_amount' => 5000]);

        $response = $this->getJson("/api/pos/analytics/sessions");

        $response->assertStatus(200);
        $this->assertEquals(5000, $response->json('data')[0]['revenue_so_far']);
        $this->assertEquals(1, $response->json('data')[0]['sales_count']);
    }

    public function test_can_get_top_products()
    {
        $this->authenticate();

        $product1 = Product::factory()->create(['title' => 'Popular Product']);
        $product2 = Product::factory()->create(['title' => 'Normal Product']);

        $order1 = Order::factory()->create();
        DB::table('order_items')->insert(['order_id' => $order1->id, 'product_id' => $product1->id, 'quantity' => 5, 'price' => 1000]);
        $this->createSale(['order_id' => $order1->id]);

        $order2 = Order::factory()->create();
        DB::table('order_items')->insert(['order_id' => $order2->id, 'product_id' => $product2->id, 'quantity' => 1, 'price' => 1000]);
        $this->createSale(['order_id' => $order2->id]);

        $response = $this->getJson("/api/pos/analytics/top-products?limit=10");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_top_products_ordered_by_revenue()
    {
        $this->authenticate();

        $product1 = Product::factory()->create(['title' => 'High Revenue']);
        $product2 = Product::factory()->create(['title' => 'Low Revenue']);

        $order1 = Order::factory()->create();
        DB::table('order_items')->insert(['order_id' => $order1->id, 'product_id' => $product1->id, 'quantity' => 1, 'price' => 8000]);
        $this->createSale(['order_id' => $order1->id]);

        $order2 = Order::factory()->create();
        DB::table('order_items')->insert(['order_id' => $order2->id, 'product_id' => $product2->id, 'quantity' => 5, 'price' => 1000]);
        $this->createSale(['order_id' => $order2->id]);

        $response = $this->getJson("/api/pos/analytics/top-products?limit=10");

        $response->assertStatus(200);
        $this->assertEquals('High Revenue', $response->json('data')[0]['name']);
        $this->assertEquals('Low Revenue', $response->json('data')[1]['name']);
    }

    public function test_period_report_requires_from_and_to_params()
    {
        $this->authenticate();

        $response = $this->getJson("/api/pos/analytics/period");

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['from', 'to']);
    }

    public function test_can_get_period_report_grouped_by_day()
    {
        $this->authenticate();

        $date1 = Carbon::now()->subDays(2);
        $date2 = Carbon::now()->subDays(1);

        $session1 = $this->createSession(['opened_at' => $date1, 'status' => 'closed']);
        $this->createSale(['session_id' => $session1->id, 'total_amount' => 1000, 'created_at' => $date1]);

        $session2 = $this->createSession(['opened_at' => $date2, 'status' => 'closed']);
        $this->createSale(['session_id' => $session2->id, 'total_amount' => 2000, 'created_at' => $date2]);

        $response = $this->getJson("/api/pos/analytics/period?from=".$date1->format('Y-m-d')."&to=".now()->format('Y-m-d')."&group_by=day");

        $response->assertStatus(200)
                 ->assertJsonPath('data.total_revenue', 3000);
        
        $this->assertEquals(1000, $response->json('data')['grouped_sales_data'][$date1->format('Y-m-d')]);
        $this->assertEquals(2000, $response->json('data')['grouped_sales_data'][$date2->format('Y-m-d')]);
    }

    public function test_can_get_period_report_grouped_by_month()
    {
        $this->authenticate();

        $date1 = Carbon::now()->subMonths(1);

        $session1 = $this->createSession(['opened_at' => $date1, 'status' => 'closed']);
        $this->createSale(['session_id' => $session1->id, 'total_amount' => 5000, 'created_at' => $date1]);

        $response = $this->getJson("/api/pos/analytics/period?from=".$date1->format('Y-m-d')."&to=".now()->format('Y-m-d')."&group_by=month");

        $response->assertStatus(200);
        $this->assertEquals(5000, $response->json('data')['grouped_sales_data'][$date1->format('Y-m')]);
    }

    public function test_can_get_low_stock_alerts()
    {
        $this->authenticate();

        Product::factory()->create(['title' => 'Low Stock Item', 'stock' => 2, 'is_active' => true]);
        Product::factory()->create(['title' => 'High Stock Item', 'stock' => 20, 'is_active' => true]);

        $response = $this->getJson("/api/pos/analytics/low-stock?threshold=5");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Low Stock Item', $response->json('data')[0]['name']);
    }

    public function test_low_stock_respects_threshold_parameter()
    {
        $this->authenticate();

        Product::factory()->create(['title' => 'Low Stock Item', 'stock' => 8, 'is_active' => true]);

        $response = $this->getJson("/api/pos/analytics/low-stock?threshold=5");
        $this->assertCount(0, $response->json('data'));

        $response2 = $this->getJson("/api/pos/analytics/low-stock?threshold=10");
        $this->assertCount(1, $response2->json('data'));
    }

    public function test_can_export_csv()
    {
        $this->authenticate();

        $this->createSession(['status' => 'closed', 'opened_at' => now(), 'closed_at' => now()]);
        
        $response = $this->get("/api/pos/analytics/export?from=".now()->subDay()->format('Y-m-d')."&to=".now()->format('Y-m-d')."&format=csv");
        
        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('Content-Disposition'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_can_export_excel()
    {
        $this->authenticate();

        $this->createSession(['status' => 'closed', 'opened_at' => now(), 'closed_at' => now()]);
        
        $response = $this->get("/api/pos/analytics/export?from=".now()->subDay()->format('Y-m-d')."&to=".now()->format('Y-m-d')."&format=excel");
        
        $response->assertStatus(200);
        $this->assertTrue($response->headers->has('Content-Disposition'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }
}

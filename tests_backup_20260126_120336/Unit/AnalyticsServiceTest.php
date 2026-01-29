<?php

namespace Tests\Unit;

use App\Models\FunnelEvent;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnalyticsService();
        Cache::flush();
    }

    #[Test]
    public function it_returns_funnel_stats(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        // Créer des événements funnel
        FunnelEvent::create([
            'event_type' => 'product_added_to_cart',
            'user_id' => 1,
            'occurred_at' => now()->subDays(2),
        ]);

        FunnelEvent::create([
            'event_type' => 'checkout_started',
            'user_id' => 1,
            'occurred_at' => now()->subDays(1),
        ]);

        $stats = $this->service->getFunnelStats($startDate, $endDate);

        $this->assertArrayHasKey('counts', $stats);
        $this->assertArrayHasKey('conversion_rates', $stats);
        $this->assertArrayHasKey('timeline', $stats);
        $this->assertEquals(1, $stats['counts']['product_added_to_cart']);
        $this->assertEquals(1, $stats['counts']['checkout_started']);
    }

    #[Test]
    public function it_returns_sales_stats(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10000]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
            'total_amount' => 10000,
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10000,
        ]);

        $stats = $this->service->getSalesStats($startDate, $endDate);

        $this->assertArrayHasKey('kpis', $stats);
        $this->assertEquals(10000, $stats['kpis']['revenue_total']);
        $this->assertEquals(1, $stats['kpis']['orders_count']);
    }

    #[Test]
    public function it_caches_funnel_stats(): void
    {
        $startDate = now()->subDays(7);
        $endDate = now();

        // Premier appel
        $stats1 = $this->service->getFunnelStats($startDate, $endDate);
        
        // Vérifier que le cache existe
        $cacheKey = sprintf('analytics:funnel:%s:%s', $startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $this->assertTrue(Cache::has($cacheKey));

        // Deuxième appel (devrait utiliser le cache)
        $stats2 = $this->service->getFunnelStats($startDate, $endDate);
        $this->assertEquals($stats1, $stats2);
    }

    #[Test]
    public function it_returns_creator_stats(): void
    {
        $startDate = now()->subDays(30);
        $endDate = now();

        $creator = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $creator->id,
            'price' => 10000,
        ]);

        $order = Order::factory()->create([
            'payment_status' => 'paid',
            'total_amount' => 10000,
            'created_at' => now()->subDays(2),
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 10000,
        ]);

        $stats = $this->service->getCreatorStats($creator->id, $startDate, $endDate);

        $this->assertArrayHasKey('kpis', $stats);
        $this->assertArrayHasKey('top_products', $stats);
        $this->assertArrayHasKey('timeline', $stats);
        $this->assertGreaterThan(0, $stats['kpis']['revenue_total']);
    }
}


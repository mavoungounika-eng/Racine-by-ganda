<?php

namespace Tests\Feature\Crm;

use App\Models\User;
use App\Models\Product;
use App\Models\CustomerSegment;
use App\Models\LoyaltyLevel;
use App\Models\LoyaltyPoint;
use App\Models\Order;
use App\Models\PosSale;
use App\Services\Crm\SegmentationService;
use App\Services\Crm\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CrmSegmentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed initial levels
        $this->seed(\Database\Seeders\LoyaltyLevelSeeder::class);
    }

    /**
     * Test loyalty points awarding for Web Orders.
     */
    public function test_loyalty_points_awarded_for_web_order()
    {
        $customer = User::factory()->create(['role' => 'client']);
        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 5000,
            'payment_status' => 'paid',
            'order_number' => 'WEB-123'
        ]);

        app(LoyaltyService::class)->awardPointsForOrder($order);

        $this->assertEquals(5, LoyaltyPoint::getBalanceFor($customer->id));
        $this->assertDatabaseHas('loyalty_points', [
            'customer_id' => $customer->id,
            'points' => 5,
            'type' => 'earned',
            'source' => 'web_order'
        ]);
    }

    /**
     * Test loyalty points awarding for POS Sales.
     */
    public function test_loyalty_points_awarded_for_pos_sale()
    {
        $customer = User::factory()->create(['role' => 'client']);
        $sale = PosSale::factory()->create([
            'customer_id' => $customer->id,
            'total_amount' => 10000,
            'status' => 'finalized'
        ]);

        app(LoyaltyService::class)->awardPointsForPosSale($sale);

        $this->assertEquals(10, LoyaltyPoint::getBalanceFor($customer->id));
    }

    /**
     * Test automatic segmentation rules.
     */
    public function test_automatic_segmentation_rules()
    {
        // 1. Create a segment for "High Spenders" (total_spent > 50000 XAF)
        $segment = CustomerSegment::create([
            'name' => 'High Spenders',
            'slug' => 'high-spenders',
            'type' => 'automatic',
            'rules' => [
                'conditions' => [
                    ['metric' => 'total_spent', 'operator' => '>', 'value' => 5000000], // in cents
                ]
            ]
        ]);

        $customer = User::factory()->create(['role' => 'client']);
        
        // Order for 60,000 XAF
        Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 60000,
            'status' => 'completed',
            'payment_status' => 'paid'
        ]);

        Cache::flush(); // Ensure metrics are recalculated

        $service = app(SegmentationService::class);
        $res = $service->syncCustomerSegments($customer);

        $this->assertContains('high-spenders', $res['added']);
        $this->assertTrue($customer->segments()->where('slug', 'high-spenders')->exists());
    }

    /**
     * Test VIP levels progression.
     */
    public function test_loyalty_level_progression()
    {
        $customer = User::factory()->create(['role' => 'client']);
        $service = app(LoyaltyService::class);

        // Award 6000 points (Should be Silver then Gold)
        $service->awardPoints($customer, 6000, 'manual', null, 'Boost');

        $level = $service->getCurrentLevel($customer);
        $this->assertEquals('vip', $level->slug); // Gold is at 2000, VIP at 5000
    }

    /**
     * Test points use (spending).
     */
    public function test_points_spending()
    {
        $customer = User::factory()->create(['role' => 'client']);
        $service = app(LoyaltyService::class);

        $service->awardPoints($customer, 100, 'manual');
        $success = $service->spendPoints($customer, 40, 'Discount');

        $this->assertTrue($success);
        $this->assertEquals(60, $service->getBalance($customer));
    }

    /**
     * Test segment metrics caching.
     */
    public function test_metrics_are_cached()
    {
        $customer = User::factory()->create(['role' => 'client']);
        $service = app(SegmentationService::class);

        $service->evaluateCustomer($customer);
        $this->assertTrue(Cache::has("crm_metrics:{$customer->id}"));
    }
}

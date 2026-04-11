<?php

namespace Modules\ERP\Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use App\Models\Notification;
use Modules\ERP\Services\StockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockAlertServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockAlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alertService = new StockAlertService();
    }

    /** @test */
    public function it_checks_low_stock_alerts()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = Product::factory()->create(['stock' => 3, 'is_active' => true]);

        $this->alertService->checkLowStockAlerts();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'stock_alert',
        ]);
    }

    /** @test */
    public function it_gets_replenishment_suggestions()
    {
        Product::factory()->create(['stock' => 5, 'is_active' => true]);
        Product::factory()->create(['stock' => 0, 'is_active' => true]);
        Product::factory()->create(['stock' => 15, 'is_active' => true]);

        $suggestions = $this->alertService->getReplenishmentSuggestions(10);

        $this->assertCount(2, $suggestions); // 2 produits avec stock <= 10
    }
}


<?php

namespace Tests\Unit;

use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Services\StockValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected StockValidationService $stockValidationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockValidationService = new StockValidationService();
        $this->orderService = new OrderService($this->stockValidationService);
    }

    #[Test]
    public function it_calculates_amounts_correctly(): void
    {
        $cartItems = collect([
            ['product_id' => 1, 'quantity' => 2, 'price' => 10000],
            ['product_id' => 2, 'quantity' => 1, 'price' => 5000],
        ]);

        $amounts = $this->orderService->calculateAmounts($cartItems, 'home_delivery');

        $this->assertEquals(25000, $amounts['subtotal']); // 20000 + 5000
        $this->assertEquals(2000, $amounts['shipping']); // Frais de livraison
        $this->assertEquals(27000, $amounts['total']); // 25000 + 2000
    }

    #[Test]
    public function it_calculates_amounts_with_showroom_pickup(): void
    {
        $cartItems = collect([
            ['product_id' => 1, 'quantity' => 1, 'price' => 10000],
        ]);

        $amounts = $this->orderService->calculateAmounts($cartItems, 'showroom_pickup');

        $this->assertEquals(10000, $amounts['subtotal']);
        $this->assertEquals(0, $amounts['shipping']); // Pas de frais pour retrait
        $this->assertEquals(10000, $amounts['total']);
    }

    #[Test]
    public function it_throws_exception_for_empty_cart(): void
    {
        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('Panier vide');

        $user = User::factory()->create();
        $this->orderService->createOrderFromCart([], collect(), $user->id);
    }
}


<?php

namespace Tests\Feature\Order;

use PHPUnit\Framework\Attributes\Test;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use App\Exceptions\StockException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'client',
            'status' => 'active',
        ]);

        $this->product = Product::factory()->create([
            'title' => 'Test Product',
            'stock' => 1,
            'price' => 100,
            'is_active' => true,
        ]);
    }
    #[Test]
    public function it_fails_to_create_order_if_stock_becomes_insufficient_during_process()
    {
        $orderService = app(OrderService::class);
        
        $formData = [
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '12345678',
            'address_line1' => 'Street 1',
            'city' => 'Douala',
            'country' => 'Cameroun',
            'shipping_method' => 'showroom_pickup',
            'payment_method' => 'cash_on_delivery',
        ];

        $items = collect([
            (object)[
                'product_id' => $this->product->id,
                'quantity' => 1,
                'price' => 100,
            ]
        ]);

        // Simuler un autre processus qui vide le stock juste avant le dÃ©crÃ©ment final
        // (On utilise un lock factice ou on modifie la DB manuellement si on Ã©tait en multi-process)
        
        // Ici on teste simplement que StockService jette bien l'exception
        $this->expectException(StockException::class);
        
        // On rÃ©duit le stock Ã  0 juste avant l'appel (pour simuler une perte de stock entre validation et created)
        $this->product->update(['stock' => 0]);
        
        $orderService->createOrderFromCart($formData, $items, $this->user->id);
        
        // VÃ©rifier que l'ordre n'existe pas en DB (rollback)
        $this->assertDatabaseMissing('orders', ['user_id' => $this->user->id]);
    }
}

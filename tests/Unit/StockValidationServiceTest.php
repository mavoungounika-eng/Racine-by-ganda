<?php

namespace Tests\Unit;

use App\Exceptions\OrderException;
use App\Exceptions\StockException;
use App\Models\Product;
use App\Services\StockValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StockValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockValidationService();
    }

    #[Test]
    public function it_validates_stock_successfully(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        
        $items = collect([
            ['product_id' => $product->id, 'quantity' => 5],
        ]);

        $result = $this->service->validateStockForCart($items);

        $this->assertTrue($result['valid']);
        $this->assertCount(1, $result['locked_products']);
        $this->assertTrue($result['locked_products']->has($product->id));
    }

    #[Test]
    public function it_throws_exception_for_insufficient_stock(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        
        $items = collect([
            ['product_id' => $product->id, 'quantity' => 10],
        ]);

        $this->expectException(StockException::class);
        $this->expectExceptionMessage('Stock insuffisant');

        $this->service->validateStockForCart($items);
    }

    #[Test]
    public function it_throws_exception_for_nonexistent_product(): void
    {
        $items = collect([
            ['product_id' => 99999, 'quantity' => 1],
        ]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('Produit introuvable');

        $this->service->validateStockForCart($items);
    }

    #[Test]
    public function it_checks_stock_issues_without_throwing(): void
    {
        $product = Product::factory()->create(['stock' => 5]);
        
        $items = collect([
            ['product_id' => $product->id, 'quantity' => 10],
        ]);

        $result = $this->service->checkStockIssues($items);

        $this->assertTrue($result['has_issues']);
        $this->assertCount(1, $result['issues']);
        $this->assertStringContainsString('Stock insuffisant', $result['issues'][0]['message']);
    }
}


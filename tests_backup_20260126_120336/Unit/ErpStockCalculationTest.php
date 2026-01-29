<?php

namespace Tests\Unit;

use App\Models\Product;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Services\StockService;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitaires pour les calculs métier ERP critiques
 * 
 * Vérifie la logique de calcul des stocks
 */
class ErpStockCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test : Calcul de valorisation du stock
     */
    public function test_stock_valuation_calculation(): void
    {
        // Créer des produits avec stock et prix
        $product1 = Product::factory()->create([
            'stock' => 10,
            'price' => 100.00,
        ]);
        
        $product2 = Product::factory()->create([
            'stock' => 5,
            'price' => 50.00,
        ]);
        
        // Calculer la valorisation attendue
        $expectedValue = (10 * 100.00) + (5 * 50.00); // 1000 + 250 = 1250
        
        // Calculer la valorisation réelle
        $actualValue = Product::where('stock', '>', 0)
            ->selectRaw('SUM(price * stock) as total_value')
            ->value('total_value');
        
        $this->assertEquals($expectedValue, $actualValue);
    }

    /**
     * Test : Décrément stock pour commande (pas de double décrément)
     */
    public function test_stock_decrement_no_double_decrement(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);
        
        $order->load('items');
        
        $stockService = new StockService();
        
        // Premier décrément
        $stockService->decrementFromOrder($order);
        $product->refresh();
        $this->assertEquals(7, $product->stock);
        
        // Tentative de double décrément (doit être ignorée)
        $stockService->decrementFromOrder($order);
        $product->refresh();
        $this->assertEquals(7, $product->stock, "Double décrément détecté !");
    }

    /**
     * Test : Calcul stock faible (low stock)
     */
    public function test_low_stock_calculation(): void
    {
        // Créer des produits avec différents stocks
        Product::factory()->create(['stock' => 10]); // OK
        Product::factory()->create(['stock' => 3]);  // Low
        Product::factory()->create(['stock' => 1]);  // Low
        Product::factory()->create(['stock' => 0]);  // Out
        
        // Calculer le nombre de produits en stock faible
        $lowStockCount = Product::where('stock', '<', 5)
            ->where('stock', '>', 0)
            ->count();
        
        $this->assertEquals(2, $lowStockCount);
    }
}


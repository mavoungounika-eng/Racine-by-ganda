<?php

namespace Tests\Feature\Erp;

use App\Events\StockAnomalyDetected;
use App\Events\StockDecremented;
use App\Events\StockLowAlert;
use App\Jobs\DecrementStockForOrder;
use App\Models\Order;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Modules\ERP\Services\StockService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Services\Pos\PosSaleService;
use App\Services\Pos\PosSessionService;

/**
 * ERP Stock Sync — Tests d'intégration stock POS ↔ Web
 *
 * Vérifie :
 * - Décrément stock lors d'une vente POS
 * - Blocage vente POS si stock = 0
 * - Décrément stock web via Observer
 * - Alerte stock bas déclenchée
 * - Throttle alerte 1/heure max
 * - Anomalie stock détectée via Job
 * - Idempotence job décrément
 * - track_stock = false ignore toutes les règles
 */
class StockSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $machineId;
    protected StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);
        $this->machineId = Str::uuid()->toString();
        $this->stockService = app(StockService::class);

        // Fake all stock events by default (each test will unfake as needed)
        Event::fake([
            StockDecremented::class,
            StockLowAlert::class,
            StockAnomalyDetected::class,
            \App\Events\PosSessionClosed::class,
            \App\Events\PosCardPaymentConfirmed::class,
            \App\Events\PosMobilePaymentConfirmed::class,
            \App\Events\OrderPlaced::class,
        ]);

        // Open a POS session for this machine
        PosSession::create([
            'machine_id'      => $this->machineId,
            'opened_by'       => $this->user->id,
            'status'          => 'open',
            'opening_balance' => 0,
            'opened_at'       => now(),
        ]);
    }

    // ─── Helper ──────────────────────────────────────────────────────

    private function makeBrandProduct(int $stock = 10, int $threshold = 5, bool $trackStock = true): Product
    {
        return Product::factory()->create([
            'product_type'       => 'brand',
            'stock'              => $stock,
            'low_stock_threshold' => $threshold,
            'track_stock'        => $trackStock,
            'price'              => 5000,
            'is_active'          => true,
        ]);
    }

    private function makePosService(): PosSaleService
    {
        return app(PosSaleService::class);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 1 — Décrément stock lors d'une vente POS
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_decrement_stock_vente_pos(): void
    {
        $product = $this->makeBrandProduct(stock: 10, threshold: 5);

        $this->makePosService()->createSale(
            $this->machineId,
            [['product_id' => $product->id, 'quantity' => 3, 'price' => 5000]],
            'cash',
            $this->user->id,
        );

        $this->assertEquals(7, $product->fresh()->stock);

        Event::assertDispatched(StockDecremented::class, function (StockDecremented $e) use ($product) {
            return $e->product_id === $product->id
                && $e->qty_removed === 3
                && $e->stock_before === 10
                && $e->stock_after === 7
                && $e->source === 'pos_sale';
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 2 — Blocage si stock = 0
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_blocage_vente_pos_si_stock_zero(): void
    {
        $product = $this->makeBrandProduct(stock: 0);

        $this->expectException(\Exception::class);

        $this->makePosService()->createSale(
            $this->machineId,
            [['product_id' => $product->id, 'quantity' => 1, 'price' => 5000]],
            'cash',
            $this->user->id,
        );

        // Stock must not have changed
        $this->assertEquals(0, $product->fresh()->stock);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 3 — Décrément stock commande web via Observer
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_decrement_stock_commande_web(): void
    {
        $product = $this->makeBrandProduct(stock: 20, threshold: 5);

        // Create an order directly — Observer fires decrementFromOrder
        $order = Order::create([
            'user_id'          => $this->user->id,
            'status'           => 'pending',
            'payment_status'   => 'pending',
            'payment_method'   => 'card',
            'total_amount'     => 10000,
            'customer_name'    => 'Test Client',
            'customer_email'   => 'test@example.com',
            'customer_address' => '123 rue Test',
            'order_number'     => 'ORD-TEST-001',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 5,
            'price'      => 2000,
        ]);

        $order->load('items');
        $observer = app(\App\Observers\OrderObserver::class);
        $observer->created($order);

        $this->assertEquals(15, $product->fresh()->stock);

        Event::assertDispatched(StockDecremented::class, fn (StockDecremented $e) =>
            $e->product_id === $product->id && $e->source === 'web_order'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 4 — Alerte stock bas déclenchée
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_alerte_stock_bas_declenchee(): void
    {
        // stock 6, threshold 5 → after removing 2 stock=4 <= threshold → alert
        $product = $this->makeBrandProduct(stock: 6, threshold: 5);

        $this->makePosService()->createSale(
            $this->machineId,
            [['product_id' => $product->id, 'quantity' => 2, 'price' => 5000]],
            'cash',
            $this->user->id,
        );

        Event::assertDispatched(StockLowAlert::class, function (StockLowAlert $e) use ($product) {
            return $e->product_id === $product->id
                && $e->current_stock === 4
                && $e->threshold === 5
                && $e->source === 'pos_sale';
        });
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 5 — Throttle alerte : 1 email max par produit par heure
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_throttle_alerte_1_par_heure(): void
    {
        $product = $this->makeBrandProduct(stock: 50, threshold: 5);

        // Simulate throttle cache key already set
        Cache::put("stock_alert:{$product->id}", 1, 3600);

        // Unfake StockLowAlert so listener can run
        Event::fake([
            StockDecremented::class,
            \App\Events\PosSessionClosed::class,
            \App\Events\PosCardPaymentConfirmed::class,
            \App\Events\PosMobilePaymentConfirmed::class,
            \App\Events\OrderPlaced::class,
        ]);

        // Dispatch the low alert directly to listener
        $listener = new \App\Listeners\HandleStockLowAlert();
        $event = new StockLowAlert(
            product_id:    $product->id,
            product_name:  $product->title,
            current_stock: 3,
            threshold:     5,
            source:        'pos_sale',
        );

        // Should return immediately (throttled) — no exception thrown
        $listener->handle($event);

        // Cache key still set
        $this->assertTrue(Cache::has("stock_alert:{$product->id}"));
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 6 — Anomalie stock détectée via Job failed()
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_anomalie_stock_detectee(): void
    {
        $product = $this->makeBrandProduct(stock: 10);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Simulate Job::failed() dispatch
        $job = new DecrementStockForOrder($order);
        $job->failed(new \Exception('Test failure'));

        Event::assertDispatched(StockAnomalyDetected::class, fn (StockAnomalyDetected $e) =>
            $e->order_id === $order->id
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 7 — Idempotence : pas de double décrément pour même ordre
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_idempotence_job_decrement(): void
    {
        $product = $this->makeBrandProduct(stock: 20);

        $order = Order::create([
            'user_id'          => $this->user->id,
            'status'           => 'pending',
            'payment_status'   => 'pending',
            'payment_method'   => 'card',
            'total_amount'     => 10000,
            'customer_name'    => 'Test',
            'customer_email'   => 'test@example.com',
            'customer_address' => 'Rue test',
            'order_number'     => 'ORD-IDMP-001',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity'   => 5,
            'price'      => 2000,
        ]);

        $order->load('items');

        // First decrement
        $this->stockService->decrementFromOrder($order);
        $this->assertEquals(15, $product->fresh()->stock);

        // Second call — should be idempotent
        $this->stockService->decrementFromOrder($order);
        $this->assertEquals(15, $product->fresh()->stock);
    }

    // ─────────────────────────────────────────────────────────────────
    // TEST 8 — track_stock = false → règles stock ignorées
    // ─────────────────────────────────────────────────────────────────

    #[Test]
    public function test_track_stock_false_ignore_regles(): void
    {
        // Product with track_stock=false and stock=0 should still sell
        $product = $this->makeBrandProduct(stock: 0, trackStock: false);

        // decrementStock should return true without throwing
        $result = $this->stockService->decrementStock($product->id, 99, 'pos_sale');
        $this->assertTrue($result);

        // Stock must remain 0 (not decremented)
        $this->assertEquals(0, $product->fresh()->stock);

        // No StockDecremented event
        Event::assertNotDispatched(StockDecremented::class);
    }
}

<?php

namespace Tests\Feature\Pos;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\PosOfflineQueue;
use App\Services\Pos\PosOfflineService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * Test Offline ↔ Online Sync Flow
 *
 * Vérifie:
 * - Détection offline/online par machine (FIX 2)
 * - Queueing ventes offline en DB (FIX 1)
 * - Sync quand reconnecté
 * - État cohérent après sync
 */
class PosOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected PosOfflineService $offlineService;
    protected User $user;
    protected string $machineId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->offlineService = new PosOfflineService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->machineId = Str::uuid()->toString();

        Event::fake([
            \App\Events\PosSessionClosed::class,
            \App\Events\PosCardPaymentConfirmed::class,
            \App\Events\PosMobilePaymentConfirmed::class,
        ]);

        PosSession::create([
            'machine_id' => $this->machineId,
            'opened_by' => $this->user->id,
            'status' => 'open',
            'opening_balance' => 0,
            'opened_at' => now(),
        ]);
    }

    #[Test]
    public function system_starts_online(): void
    {
        $this->assertFalse($this->offlineService->isOffline($this->machineId));
    }

    #[Test]
    public function system_can_be_marked_offline(): void
    {
        // FIX 2 : markOffline prend machine_id + reason
        $this->offlineService->markOffline($this->machineId, 'Redis unavailable');
        $this->assertTrue($this->offlineService->isOffline($this->machineId));

        $status = $this->offlineService->getOfflineStatus($this->machineId);
        $this->assertNotNull($status);
        $this->assertEquals('Redis unavailable', $status['reason']);
        $this->assertEquals($this->machineId, $status['machine_id']);
    }

    #[Test]
    public function system_can_be_marked_online(): void
    {
        $this->offlineService->markOffline($this->machineId, 'Test');
        $this->assertTrue($this->offlineService->isOffline($this->machineId));

        // FIX 2 : markOnline prend machine_id
        $this->offlineService->markOnline($this->machineId);
        $this->assertFalse($this->offlineService->isOffline($this->machineId));
    }

    #[Test]
    public function offline_sales_are_queued(): void
    {
        $saleData = [
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'price' => 50.00],
            ],
            'total_amount'   => 100.00,
            'payment_method' => 'cash',
        ];

        // FIX 1 : queue persistée en DB
        $this->offlineService->queueOfflineSale($this->machineId, $saleData);
        $this->offlineService->queueOfflineSale($this->machineId, $saleData);

        $this->assertDatabaseCount('pos_offline_queue', 2);
        $this->assertEquals(2, $this->offlineService->getOfflineQueueCount());
    }

    #[Test]
    public function offline_queue_can_be_flushed(): void
    {
        $saleData = [
            'items'          => [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]],
            'total_amount'   => 100.00,
            'payment_method' => 'cash',
        ];

        $this->offlineService->queueOfflineSale($this->machineId, $saleData);
        $this->assertEquals(1, $this->offlineService->getOfflineQueueCount());

        $queue = $this->offlineService->flushOfflineQueue();

        $this->assertArrayHasKey($this->machineId, $queue);
        $this->assertCount(1, $queue[$this->machineId]);

        // FIX 1 : après flush, status = 'synced', count pending = 0
        $this->assertEquals(0, $this->offlineService->getOfflineQueueCount());
        $this->assertDatabaseHas('pos_offline_queue', ['status' => 'synced']);
    }

    #[Test]
    public function multiple_machines_can_queue_independently(): void
    {
        $machine1 = Str::uuid()->toString();
        $machine2 = Str::uuid()->toString();

        $saleData = ['total_amount' => 50.00];

        $this->offlineService->queueOfflineSale($machine1, $saleData);
        $this->offlineService->queueOfflineSale($machine1, $saleData);
        $this->offlineService->queueOfflineSale($machine2, $saleData);

        $this->assertEquals(3, $this->offlineService->getOfflineQueueCount());

        $queue = $this->offlineService->flushOfflineQueue();
        $this->assertCount(2, $queue[$machine1]);
        $this->assertCount(1, $queue[$machine2]);
    }

    #[Test]
    public function offline_mode_timeout_clears_after_expiration(): void
    {
        $this->offlineService->markOffline($this->machineId, 'Test');
        $this->assertTrue($this->offlineService->isOffline($this->machineId));

        // Simuler expiration via Cache::flush()
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertFalse($this->offlineService->isOffline($this->machineId));
    }

    #[Test]
    public function full_offline_to_online_cycle(): void
    {
        // 1. Système online
        $this->assertFalse($this->offlineService->isOffline($this->machineId));

        // 2. Marquer offline
        $this->offlineService->markOffline($this->machineId, 'Network lost');
        $this->assertTrue($this->offlineService->isOffline($this->machineId));

        // 3. Queue ventes (FIX 1 : en DB)
        $sale1 = ['total_amount' => 100.00, 'payment_method' => 'cash'];
        $sale2 = ['total_amount' => 150.00, 'payment_method' => 'card'];

        $this->offlineService->queueOfflineSale($this->machineId, $sale1);
        $this->offlineService->queueOfflineSale($this->machineId, $sale2);

        $this->assertEquals(2, $this->offlineService->getOfflineQueueCount());

        // 4. Reconnecter (FIX 2 : par machine)
        $this->offlineService->markOnline($this->machineId);
        $this->assertFalse($this->offlineService->isOffline($this->machineId));

        // 5. Flush queue
        $queue = $this->offlineService->flushOfflineQueue();

        // 6. Vérifier données intactes (FIX 1 : données en DB)
        $this->assertCount(2, $queue[$this->machineId]);
        $this->assertEquals(100.00, $queue[$this->machineId][0]['data']['total_amount']);
        $this->assertEquals(150.00, $queue[$this->machineId][1]['data']['total_amount']);

        // 7. Queue vide après flush
        $this->assertEquals(0, $this->offlineService->getOfflineQueueCount());
    }

    #[Test]
    public function offline_queue_survives_cache_flush(): void
    {
        // FIX 1 : la queue est en DB, pas en Cache — elle survit à Cache::flush()
        $saleData = ['total_amount' => 99.00, 'payment_method' => 'mobile_money'];

        $this->offlineService->queueOfflineSale($this->machineId, $saleData);
        $this->assertEquals(1, $this->offlineService->getOfflineQueueCount());

        // Simuler redémarrage Redis
        \Illuminate\Support\Facades\Cache::flush();

        // La vente est toujours là (en DB)
        $this->assertEquals(1, $this->offlineService->getOfflineQueueCount());
    }

    #[Test]
    public function offline_is_per_machine_not_global(): void
    {
        // FIX 2 : machine A offline ne rend pas machine B offline
        $machineA = Str::uuid()->toString();
        $machineB = Str::uuid()->toString();

        $this->offlineService->markOffline($machineA, 'A lost network');

        $this->assertTrue($this->offlineService->isOffline($machineA));
        $this->assertFalse($this->offlineService->isOffline($machineB));
    }

    // =========================================================================
    // NOUVEAUX TESTS (OFFLINE SYNC & CONFLICTS)
    // =========================================================================

    #[Test]
    public function test_sync_reussie_n_ventes(): void
    {
        $product = Product::factory()->create([
            'stock' => 10,
            'price' => 50,
            'product_type' => 'brand'
        ]);

        $uuid1 = Str::uuid()->toString();
        $uuid2 = Str::uuid()->toString();

        $sales = [
            [
                'uuid' => $uuid1,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 50.00]],
                'total_amount' => 50.00,
                'payment_method' => 'cash',
            ],
            [
                'uuid' => $uuid2,
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 50.00]],
                'total_amount' => 100.00,
                'payment_method' => 'cash',
            ]
        ];

        $res = $this->offlineService->syncPendingSales($this->machineId, $sales, $this->user->id);

        $this->assertEquals(2, $res['synced']);
        $this->assertEquals(0, $res['failed']);
        $this->assertCount(0, $res['conflicts']);
        $this->assertDatabaseHas('pos_sales', ['uuid' => $uuid1]);
        $this->assertDatabaseHas('pos_sales', ['uuid' => $uuid2]);
    }

    #[Test]
    public function test_detection_conflit_stock(): void
    {
        $product = Product::factory()->create(['stock' => 1]);

        $uuid = Str::uuid()->toString();
        $sales = [
            [
                'uuid' => $uuid,
                'items' => [['product_id' => $product->id, 'quantity' => 2, 'price' => 50.00]],
                'total_amount' => 100.00,
                'payment_method' => 'cash',
            ]
        ];

        $res = $this->offlineService->syncPendingSales($this->machineId, $sales, $this->user->id);

        $this->assertEquals(0, $res['synced']);
        $this->assertCount(1, $res['conflicts']);
        $this->assertDatabaseHas('pos_offline_queue', [
            'status' => 'conflict',
            'error_message' => 'SYNC_CONFLICT_STOCK'
        ]);
        
        // La vente n'a pas été créée
        $this->assertDatabaseMissing('pos_sales', ['uuid' => $uuid]);
    }

    #[Test]
    public function test_idempotence_double_sync(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $uuid = Str::uuid()->toString();
        $sales = [
            [
                'uuid' => $uuid,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 50.00]],
                'total_amount' => 50.00,
                'payment_method' => 'cash',
            ]
        ];

        // 1ere sync
        $res1 = $this->offlineService->syncPendingSales($this->machineId, $sales, $this->user->id);
        $this->assertEquals(1, $res1['synced']);

        // 2eme sync involontaire
        $res2 = $this->offlineService->syncPendingSales($this->machineId, $sales, $this->user->id);
        
        $this->assertEquals(1, $res2['synced']); // Indique qu'elle est "traitée" (déjà sync)
        
        // Mais 1 seul enregistrement en BD
        $this->assertEquals(1, PosSale::where('uuid', $uuid)->count());
    }

    #[Test]
    public function test_expiration_24h(): void
    {
        // Forcer la création avec une date ancienne
        PosOfflineQueue::create([
            'machine_id' => $this->machineId,
            'sale_data' => '{}',
            'status' => 'pending',
            'queued_at' => now()->subHours(25),
        ]);

        $expired = $this->offlineService->expireOldSales();

        $this->assertEquals(1, $expired);
        $this->assertDatabaseHas('pos_offline_queue', ['status' => 'expired']);
    }

    #[Test]
    public function test_resolution_force_apply(): void
    {
        $product = Product::factory()->create(['stock' => 0]); // Conflit dès le départ
        $uuid = Str::uuid()->toString();
        
        $sales = [
            [
                'uuid' => $uuid,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 50.00]],
                'total_amount' => 50.00,
                'payment_method' => 'cash',
            ]
        ];

        // Provoque le conflit
        $this->offlineService->syncPendingSales($this->machineId, $sales, $this->user->id);
        
        // Résolution conflict : force_apply
        $success = $this->offlineService->resolveConflict($uuid, 'force_apply', $this->user->id);
        
        $this->assertTrue($success);
        $this->assertDatabaseHas('pos_sales', ['uuid' => $uuid]);
        $this->assertDatabaseHas('pos_offline_queue', [
            'status' => 'synced'
        ]);
    }

    #[Test]
    public function test_resolution_discard(): void
    {
        $product = Product::factory()->create(['stock' => 0]); 
        $uuid = Str::uuid()->toString();
        
        $sales = [
            [
                'uuid' => $uuid,
                'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 50.00]],
                'total_amount' => 50.00,
                'payment_method' => 'cash',
            ]
        ];

        // Provoque le conflit
        $this->offlineService->syncPendingSales($this->machineId, $sales, $this->user->id);
        
        // Résolution conflict : discard
        $success = $this->offlineService->resolveConflict($uuid, 'discard', $this->user->id);
        
        $this->assertTrue($success);
        $this->assertDatabaseMissing('pos_sales', ['uuid' => $uuid]); // annulée
        $this->assertDatabaseHas('pos_offline_queue', [
            'status' => 'discarded'
        ]);
    }
}

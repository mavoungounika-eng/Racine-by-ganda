<?php

namespace Tests\Feature\Pos;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Services\Pos\PosOfflineService;
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
}

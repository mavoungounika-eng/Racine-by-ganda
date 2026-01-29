<?php

namespace Tests\Feature\Pos;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PosSession;
use App\Services\Pos\PosOfflineService;
use Illuminate\Support\Str;

/**
 * Test Offline ↔ Online Sync Flow
 *
 * Vérifie:
 * - Détection offline/online
 * - Queueing ventes offline
 * - Sync quand reconnecté
 * - État cohérent après sync
 */
class PosOfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected PosOfflineService $offlineService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->offlineService = new PosOfflineService();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function system_starts_online()
    {
        $this->assertFalse($this->offlineService->isOffline());
    }

    /** @test */
    public function system_can_be_marked_offline()
    {
        $this->offlineService->markOffline('Redis unavailable');
        $this->assertTrue($this->offlineService->isOffline());

        $status = $this->offlineService->getOfflineStatus();
        $this->assertNotNull($status);
        $this->assertEquals('Redis unavailable', $status['reason']);
    }

    /** @test */
    public function system_can_be_marked_online()
    {
        $this->offlineService->markOffline('Test');
        $this->assertTrue($this->offlineService->isOffline());

        $this->offlineService->markOnline();
        $this->assertFalse($this->offlineService->isOffline());
    }

    /** @test */
    public function offline_sales_are_queued()
    {
        $machineId = Str::uuid()->toString();
        $saleData = [
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'price' => 50.00],
            ],
            'total_amount' => 100.00,
            'payment_method' => 'cash',
        ];

        // Queue ventes offline
        $this->offlineService->queueOfflineSale($machineId, $saleData);
        $this->offlineService->queueOfflineSale($machineId, $saleData);

        // Vérifier count
        $this->assertEquals(2, $this->offlineService->getOfflineQueueCount());
    }

    /** @test */
    public function offline_queue_can_be_flushed()
    {
        $machineId = Str::uuid()->toString();
        $saleData = [
            'items' => [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]],
            'total_amount' => 100.00,
            'payment_method' => 'cash',
        ];

        $this->offlineService->queueOfflineSale($machineId, $saleData);
        $this->assertEquals(1, $this->offlineService->getOfflineQueueCount());

        // Flush queue
        $queue = $this->offlineService->flushOfflineQueue();

        // Vérifier contenu
        $this->assertArrayHasKey($machineId, $queue);
        $this->assertCount(1, $queue[$machineId]);

        // Vérifier queue vidée
        $this->assertEquals(0, $this->offlineService->getOfflineQueueCount());
    }

    /** @test */
    public function multiple_machines_can_queue_independently()
    {
        $machine1 = Str::uuid()->toString();
        $machine2 = Str::uuid()->toString();

        $saleData = ['total_amount' => 50.00];

        // Queue pour machine 1
        $this->offlineService->queueOfflineSale($machine1, $saleData);
        $this->offlineService->queueOfflineSale($machine1, $saleData);

        // Queue pour machine 2
        $this->offlineService->queueOfflineSale($machine2, $saleData);

        // Total: 3
        $this->assertEquals(3, $this->offlineService->getOfflineQueueCount());

        // Flush et vérifier structure
        $queue = $this->offlineService->flushOfflineQueue();
        $this->assertCount(2, $queue[$machine1]);
        $this->assertCount(1, $queue[$machine2]);
    }

    /** @test */
    public function offline_mode_timeout_clears_after_expiration()
    {
        // Cache duration = 3600 secondes
        $this->offlineService->markOffline('Test');
        $this->assertTrue($this->offlineService->isOffline());

        // Avancer temps (simulé via cache backend)
        // En test, on simule simplement l'expiration
        \Illuminate\Support\Facades\Cache::flush();

        $this->assertFalse($this->offlineService->isOffline());
    }

    /** @test */
    public function full_offline_to_online_cycle()
    {
        $machineId = Str::uuid()->toString();

        // 1. Système online
        $this->assertFalse($this->offlineService->isOffline());

        // 2. Marquer offline
        $this->offlineService->markOffline('Network lost');
        $this->assertTrue($this->offlineService->isOffline());

        // 3. Queue ventes
        $sale1 = ['total_amount' => 100.00, 'payment_method' => 'cash'];
        $sale2 = ['total_amount' => 150.00, 'payment_method' => 'card'];

        $this->offlineService->queueOfflineSale($machineId, $sale1);
        $this->offlineService->queueOfflineSale($machineId, $sale2);

        $this->assertEquals(2, $this->offlineService->getOfflineQueueCount());

        // 4. Reconnecter
        $this->offlineService->markOnline();
        $this->assertFalse($this->offlineService->isOffline());

        // 5. Flush queue pour processing
        $queue = $this->offlineService->flushOfflineQueue();

        // 6. Vérifier données intactes
        $this->assertCount(2, $queue[$machineId]);
        $this->assertEquals(100.00, $queue[$machineId][0]['data']['total_amount']);
        $this->assertEquals(150.00, $queue[$machineId][1]['data']['total_amount']);

        // 7. Queue vide après flush
        $this->assertEquals(0, $this->offlineService->getOfflineQueueCount());
    }
}

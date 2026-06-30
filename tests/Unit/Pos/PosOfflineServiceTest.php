<?php

namespace Tests\Unit\Pos;

use App\Services\Pos\PosOfflineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosOfflineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PosOfflineService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new PosOfflineService();
    }

    public function test_marks_machine_as_offline(): void
    {
        $machineId = (string) Str::uuid();
        $this->service->markOffline($machineId, 'Network down');

        $this->assertTrue($this->service->isOffline($machineId));
        $status = $this->service->getOfflineStatus($machineId);
        $this->assertEquals($machineId, $status['machine_id']);
        $this->assertEquals('Network down', $status['reason']);
    }

    public function test_marks_machine_as_online(): void
    {
        $machineId = (string) Str::uuid();
        $this->service->markOffline($machineId, 'Test');
        $this->service->markOnline($machineId);

        $this->assertFalse($this->service->isOffline($machineId));
    }

    public function test_queues_sale_when_offline(): void
    {
        $machineId = (string) Str::uuid();
        $saleData = ['items' => [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]]];

        $this->service->queueOfflineSale($machineId, $saleData);

        $this->assertDatabaseHas('pos_offline_queue', [
            'machine_id' => $machineId,
            'status' => 'pending',
        ]);
    }

    public function test_processes_queued_sales_when_back_online(): void
    {
        $machineId = (string) Str::uuid();
        $saleData = ['items' => [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]]];

        $this->service->queueOfflineSale($machineId, $saleData);
        $result = $this->service->flushOfflineQueue($machineId);

        $this->assertArrayHasKey($machineId, $result);
        $this->assertEquals('synced', DB::table('pos_offline_queue')->first()->status);
    }

    public function test_skips_already_processed_queued_sales(): void
    {
        $machineId = (string) Str::uuid();
        DB::table('pos_offline_queue')->insert([
            'machine_id' => $machineId,
            'sale_data' => json_encode(['items' => []]),
            'status' => 'synced',
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->service->flushOfflineQueue($machineId);
        $this->assertEmpty($result);
    }

    public function test_returns_correct_offline_status(): void
    {
        $machineId = (string) Str::uuid();
        $this->service->markOffline($machineId, 'Redis unavailable');

        $status = $this->service->getOfflineStatus($machineId);
        $this->assertEquals('Redis unavailable', $status['reason']);
        $this->assertEquals($machineId, $status['machine_id']);
    }

    public function test_queue_count_reflects_pending_items(): void
    {
        $m1 = (string) Str::uuid();
        $m2 = (string) Str::uuid();
        $saleData = ['items' => [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]]];

        $this->service->queueOfflineSale($m1, $saleData);
        $this->service->queueOfflineSale($m2, $saleData);
        DB::table('pos_offline_queue')->insert([
            'machine_id' => $m2,
            'sale_data' => json_encode(['items' => []]),
            'status' => 'synced',
            'queued_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(2, $this->service->getOfflineQueueCount());
    }

    public function test_handles_empty_queue_gracefully(): void
    {
        $result = $this->service->flushOfflineQueue();
        $this->assertEquals([], $result);
    }
}

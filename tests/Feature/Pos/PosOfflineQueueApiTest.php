<?php

namespace Tests\Feature\Pos;

use App\Models\PosOfflineQueue;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\POSSync\Models\PosDevice;
use Modules\POSSync\Services\DeviceAuthService;
use Tests\TestCase;

class PosOfflineQueueApiTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveDeviceWithUser(User $user): PosDevice
    {
        return PosDevice::create([
            'machine_id' => (string) Str::uuid(),
            'name' => 'POS-Device',
            'machine_secret' => 'secret-key',
            'status' => 'active',
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);
    }

    private function authHeaderForDevice(PosDevice $device): array
    {
        $token = app(DeviceAuthService::class)->generateToken($device->machine_id);

        return ['Authorization' => "Bearer {$token}"];
    }

    private function createQueuedItem(string $machineId, string $status = 'pending'): PosOfflineQueue
    {
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 10]);

        return PosOfflineQueue::create([
            'machine_id' => $machineId,
            'sale_data' => [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'price' => 100.00,
                    ],
                ],
                'payment_method' => 'cash',
            ],
            'status' => $status,
            'queued_at' => now()->subMinutes(10),
            'attempts' => 0,
            'error_message' => null,
        ]);
    }

    private function openSessionForDevice(PosDevice $device): void
    {
        $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 1000.00,
        ], $this->authHeaderForDevice($device))->assertStatus(201);
    }

    public function test_can_get_offline_status_for_machine(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $response = $this->getJson('/api/pos/offline/status?machine_id=' . $device->machine_id, $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('data.machine_id', $device->machine_id);
    }

    public function test_offline_status_shows_queue_count(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->createQueuedItem($device->machine_id);
        $this->createQueuedItem($device->machine_id);

        $response = $this->getJson('/api/pos/offline/status?machine_id=' . $device->machine_id, $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('data.queue_count', 2);
    }

    public function test_can_list_queued_sales(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->createQueuedItem($device->machine_id);

        $response = $this->getJson('/api/pos/offline/queue', $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_can_filter_queue_by_status(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->createQueuedItem($device->machine_id, 'pending');
        $this->createQueuedItem($device->machine_id, 'failed');

        $response = $this->getJson('/api/pos/offline/queue?status=failed', $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_can_submit_single_queued_item(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->openSessionForDevice($device);
        $item = $this->createQueuedItem($device->machine_id);

        $response = $this->postJson("/api/pos/offline/queue/{$item->id}/submit", [], $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'processed');
    }

    public function test_cannot_submit_item_from_different_machine(): void
    {
        $userA = User::factory()->create();
        $deviceA = $this->createActiveDeviceWithUser($userA);
        $item = $this->createQueuedItem($deviceA->machine_id);

        $userB = User::factory()->create();
        $deviceB = $this->createActiveDeviceWithUser($userB);

        $response = $this->postJson("/api/pos/offline/queue/{$item->id}/submit", [], $this->authHeaderForDevice($deviceB));
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'MACHINE_MISMATCH');
    }

    public function test_can_flush_entire_queue(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->openSessionForDevice($device);
        $this->createQueuedItem($device->machine_id);
        $this->createQueuedItem($device->machine_id);

        $response = $this->postJson('/api/pos/offline/queue/flush', [], $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('data.processed', 2);
    }

    public function test_flush_returns_processed_and_failed_counts(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->openSessionForDevice($device);
        $this->createQueuedItem($device->machine_id);

        $response = $this->postJson('/api/pos/offline/queue/flush', [], $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['processed', 'failed', 'errors']]);
    }

    public function test_can_clear_old_processed_items(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $item = $this->createQueuedItem($device->machine_id, 'synced');
        $item->update([
            'synced_at' => now()->subHours(30),
            'updated_at' => now()->subHours(30),
        ]);

        $response = $this->deleteJson('/api/pos/offline/queue', [], $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('data.deleted', 1);
    }

    public function test_clear_does_not_remove_pending_items(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->createQueuedItem($device->machine_id, 'pending');

        $response = $this->deleteJson('/api/pos/offline/queue', [], $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $this->assertEquals(1, PosOfflineQueue::where('machine_id', $device->machine_id)->count());
    }

    public function test_unauthenticated_request_returns_200_ping(): void
    {
        // Route publique intentionnelle — le POS ping AVANT auth/registration
        $response = $this->getJson('/api/pos/offline/status');
        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'online');
    }
}

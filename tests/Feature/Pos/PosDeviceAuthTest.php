<?php

namespace Tests\Feature\Pos;

use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\POSSync\Models\PosDevice;
use Modules\POSSync\Services\DeviceAuthService;
use Tests\TestCase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Models\Product;
use App\Models\User;

class PosDeviceAuthTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveDeviceWithUser(User $user, ?string $machineId = null): PosDevice
    {
        return PosDevice::create([
            'machine_id' => $machineId ?? (string) Str::uuid(),
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

    public function test_authenticated_device_can_open_session(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $response = $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], $this->authHeaderForDevice($device));

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.session.machine_id', $device->machine_id);
        $response->assertJsonPath('data.session.status', 'open');
    }

    public function test_authenticated_device_can_create_sale(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 1000.00,
        ], $this->authHeaderForDevice($device))->assertStatus(201);

        $product = Product::factory()->create(['price' => 100.00, 'stock' => 50]);

        $response = $this->postJson('/api/pos/sales', [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'price' => 100.00,
                ],
            ],
            'payment_method' => 'cash',
        ], $this->authHeaderForDevice($device));

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.sale.payment_method', 'cash');
    }

    public function test_authenticated_device_can_get_current_session(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 5000.00,
        ], $this->authHeaderForDevice($device))->assertStatus(201);

        $response = $this->getJson('/api/pos/sessions/current', $this->authHeaderForDevice($device));
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.session.machine_id', $device->machine_id);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/pos/sessions/current');
        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_expired_token_returns_401(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);
        $secret = config('jwt.secret');

        $payload = [
            'iss' => config('app.name'),
            'sub' => $device->machine_id,
            'iat' => time() - 3600,
            'exp' => time() - 10,
        ];
        $token = JWT::encode($payload, $secret, config('jwt.algo', 'HS256'));

        $response = $this->getJson('/api/pos/sessions/current', [
            'Authorization' => "Bearer {$token}",
        ]);
        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_invalid_token_returns_401(): void
    {
        $response = $this->getJson('/api/pos/sessions/current', [
            'Authorization' => 'Bearer invalid.token.here',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    }

    public function test_device_from_different_machine_cannot_access_session(): void
    {
        $userA = User::factory()->create();
        $deviceA = $this->createActiveDeviceWithUser($userA);
        $this->postJson('/api/pos/sessions/open', [
            'opening_cash' => 1000.00,
        ], $this->authHeaderForDevice($deviceA))->assertStatus(201);

        $sessionId = \App\Models\PosSession::where('machine_id', $deviceA->machine_id)->value('id');

        $userB = User::factory()->create();
        $deviceB = $this->createActiveDeviceWithUser($userB);

        $tokenB = app(DeviceAuthService::class)->generateToken($deviceB->machine_id);
        $this->assertNotNull(app(DeviceAuthService::class)->validateToken($tokenB));

        $response = $this->withHeaders(['Authorization' => "Bearer {$tokenB}"])
            ->getJson("/api/pos/sessions/{$sessionId}/prepare-close");
        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'MACHINE_MISMATCH');
    }

    public function test_api_routes_work_with_device_auth(): void
    {
        $user = User::factory()->create();
        $device = $this->createActiveDeviceWithUser($user);

        $response = $this->postJson('/api/pos/sessions/open', [
            'machine_id' => $device->machine_id,
            'opening_cash' => 5000.00,
        ], array_merge($this->authHeaderForDevice($device), [
            'X-Idempotency-Key' => (string) Str::uuid(),
        ]));

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }
}

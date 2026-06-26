<?php
namespace Tests\Traits;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\POSSync\Models\PosDevice;
use Modules\POSSync\Services\DeviceAuthService;

trait CreatesPosDevice
{
    protected function createActiveDevice(User $user, ?string $machineId = null): PosDevice
    {
        return PosDevice::create([
            'machine_id' => $machineId ?? (string) Str::uuid(),
            'name'          => 'POS-Device',
            'machine_secret' => 'secret-key',
            'status'        => 'active',
            'metadata'      => ['user_id' => $user->id],
        ]);
    }

    protected function deviceAuthHeader(PosDevice $device): array
    {
        $token = app(DeviceAuthService::class)->generateToken($device->machine_id);
        return ['Authorization' => "Bearer {$token}"];
    }
}

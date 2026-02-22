<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\POSSync\Services\DeviceAuthService;
use Symfony\Component\HttpFoundation\Response;

class PosDeviceAuth
{
    public function __construct(
        private DeviceAuthService $deviceAuthService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Missing bearer token'], 401);
        }

        $device = $this->deviceAuthService->validateToken($token);

        if (!$device) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        if (!$device->isActive()) {
            return response()->json([
                'error' => 'Device not active',
                'status' => $device->status,
            ], 403);
        }

        // Make device available downstream without re-decoding JWT everywhere.
        $request->attributes->set('pos_device', $device);

        return $next($request);
    }
}

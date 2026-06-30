<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Responses\PosApiResponse;
use Modules\POSSync\Services\DeviceAuthService;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class PosDeviceAuth
{
    public function __construct(
        private DeviceAuthService $deviceAuthService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        \Log::info('PosDeviceAuth middleware triggered', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'bearer_token' => $request->bearerToken() ? 'present' : 'missing',
        ]);

        $token = $request->bearerToken();

        if (!$token) {
            return PosApiResponse::unauthorized('Missing bearer token');
        }

        $device = $this->deviceAuthService->validateToken($token);

        if (!$device) {
            return PosApiResponse::unauthorized('Invalid token');
        }

        if (!$device->isActive()) {
            return PosApiResponse::error(
                'DEVICE_NOT_ACTIVE',
                'Device not active',
                ['status' => $device->status],
                403
            );
        }

        // Check for operator authentication
        $operatorToken = $request->header('X-Operator-Token');
        if ($operatorToken) {
            $accessToken = PersonalAccessToken::findToken($operatorToken);
            if (!$accessToken || !$accessToken->tokenable) {
                return PosApiResponse::unauthorized('Invalid operator token');
            }
            if (!$accessToken->can('pos:operate')) {
                return PosApiResponse::unauthorized('Operator token invalid for POS');
            }
            $request->posOperator = $accessToken->tokenable;
        }

        // Make device available downstream without re-decoding JWT everywhere.
        $request->attributes->set('pos_device', $device);
        $request->posDevice = $device;
        $request->machineId = $device->machine_id;
        $request->posUserId = $device->metadata['user_id'] ?? null;

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\POSSync\Models\PosDevice;

class PosDeviceController extends Controller
{
    public function activate(PosDevice $device): JsonResponse
    {
        $device->update([
            'status' => 'active',
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $device->id,
                'machine_id' => $device->machine_id,
                'name' => $device->name,
                'status' => $device->status,
                'blocked_at' => $device->blocked_at,
                'blocked_reason' => $device->blocked_reason,
            ],
            'message' => 'POS terminal activated successfully.',
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\PosSyncRequest;
use App\Services\Pos\PosConnectService;
use Illuminate\Http\JsonResponse;

/**
 * PosSyncController — synchronisation des ventes offline (Electron).
 *
 * POST /api/pos/v1/sync { orders: [{ offline_id, items[], ... }] }
 * → { success: true, data: { synced_ids: [], skipped_ids: [], failed: [] } }
 *
 * Idempotent : un offline_id déjà synchronisé est ignoré (skipped),
 * jamais réinséré.
 */
class PosSyncController extends Controller
{
    public function __construct(
        protected PosConnectService $posConnect,
    ) {
    }

    public function sync(PosSyncRequest $request): JsonResponse
    {
        $result = $this->posConnect->syncOfflineSales(
            $request->user(),
            $request->validated('orders'),
        );

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }
}

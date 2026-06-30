<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Responses\PosApiResponse;
use App\Services\Pos\PosOfflineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PosOfflineSyncController extends Controller
{
    public function __construct(
        protected PosOfflineService $offlineService
    ) {}

    /**
     * POST /api/pos/offline/sync
     * Synchronise les ventes offline depuis Electron
     */
    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string',
            'sales' => 'required|array|min:1',
            'sales.*.uuid' => 'required|uuid',
            'sales.*.items' => 'required|array',
            'sales.*.total_amount' => 'required|numeric',
        ]);

        $userId = $request->user()->id;

        try {
            $result = $this->offlineService->syncPendingSales(
                $validated['device_id'],
                $validated['sales'],
                $userId
            );

            return PosApiResponse::success($result, 'Synchronisation terminée');
        } catch (\Exception $e) {
            Log::error('Offline Sync Error: ' . $e->getMessage());
            return PosApiResponse::error('Erreur lors de la synchronisation', 500);
        }
    }

    /**
     * POST /api/pos/offline/conflict/resolve
     * Résoudre manuellement un conflit de stock
     */
    public function resolveConflict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_uuid' => 'required|uuid',
            'resolution' => 'required|in:force_apply,discard',
        ]);

        $userId = $request->user()->id;

        try {
            $success = $this->offlineService->resolveConflict(
                $validated['sale_uuid'],
                $validated['resolution'],
                $userId
            );

            if ($success) {
                return PosApiResponse::success(['resolved' => true], 'Conflit résolu avec succès');
            }

            return PosApiResponse::error('Impossible de résoudre le conflit ou conflit introuvable', 400);
        } catch (\Exception $e) {
            Log::error('Offline Resolve Conflict Error: ' . $e->getMessage());
            return PosApiResponse::error('Erreur technique lors de la résolution du conflit', 500);
        }
    }
}

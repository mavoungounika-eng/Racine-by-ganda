<?php

namespace App\Http\Controllers\Pos;

use App\Http\Resources\Pos\PosOfflineQueueResource;
use App\Models\PosOfflineQueue;
use App\Services\Pos\PosOfflineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosOfflineController extends PosApiController
{
    public function __construct(
        protected PosOfflineService $offlineService
    ) {}

    public function status(Request $request): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');

        // No machine_id → simple connectivity ping (public route, no auth).
        if (!$machineId) {
            return $this->success([
                'status' => 'online',
                'timestamp' => now()->toISOString(),
            ]);
        }

        $status = $this->offlineService->getOfflineStatus($machineId);
        $queueCount = PosOfflineQueue::where('machine_id', $machineId)
            ->where('status', 'pending')
            ->count();
        $oldest = PosOfflineQueue::where('machine_id', $machineId)
            ->where('status', 'pending')
            ->orderBy('queued_at')
            ->value('queued_at');

        return $this->success([
            'is_offline' => $this->offlineService->isOffline($machineId),
            'machine_id' => $machineId,
            'reason' => $status['reason'] ?? null,
            'since' => $status['marked_at'] ?? null,
            'queue_count' => $queueCount,
            'oldest_queued_at' => $oldest,
        ]);
    }

    public function queue(Request $request): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');
        if (!$machineId) {
            return $this->error('INVALID_MACHINE_ID', 'machine_id is required');
        }

        $status = $request->query('status', 'pending');
        if ($status === 'processed') {
            $status = 'synced';
        }

        $query = PosOfflineQueue::query()
            ->where('machine_id', $machineId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('queued_at');

        $items = $query->paginate(20);

        return $this->success([
            'data' => PosOfflineQueueResource::collection($items),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function submit(Request $request, PosOfflineQueue $item): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');
        if (!$machineId) {
            return $this->error('INVALID_MACHINE_ID', 'machine_id is required');
        }

        if ($item->machine_id !== $machineId) {
            return $this->error('MACHINE_MISMATCH', 'Queue item does not belong to this device', null, 403);
        }

        $userId = $request->posUserId ?? $request->posOperator?->id ?? \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
        }

        $result = $this->offlineService->processQueueItem($item, $userId);

        if (!$result['success']) {
            return $this->error('QUEUE_ITEM_FAILED', $result['error'] ?? 'Failed to process item', null, 400);
        }

        return $this->success([
            'sale_id' => $result['sale']->id,
            'status' => 'processed',
        ], 'Queue item processed');
    }

    public function flush(Request $request): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');
        if (!$machineId) {
            return $this->error('INVALID_MACHINE_ID', 'machine_id is required');
        }

        $userId = $request->posUserId ?? $request->posOperator?->id ?? \Illuminate\Support\Facades\Auth::id();
        if (!$userId) {
            return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
        }

        $items = PosOfflineQueue::query()
            ->where('machine_id', $machineId)
            ->where('status', 'pending')
            ->orderBy('queued_at')
            ->get();

        $processed = 0;
        $failed = 0;
        $errors = [];

        foreach ($items as $item) {
            $result = $this->offlineService->processQueueItem($item, $userId);
            if ($result['success']) {
                $processed++;
            } else {
                $failed++;
                $errors[] = [
                    'item_id' => $item->id,
                    'error' => $result['error'] ?? 'Failed to process item',
                ];
            }
        }

        return $this->success([
            'processed' => $processed,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    public function clear(Request $request): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');
        if (!$machineId) {
            return $this->error('INVALID_MACHINE_ID', 'machine_id is required');
        }

        $deleted = $this->offlineService->clearOldItems($machineId, 24);

        return $this->success([
            'deleted' => $deleted,
        ]);
    }
}

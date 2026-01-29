<?php

namespace App\Http\Controllers\Pos;

use App\Services\Pos\PosOfflineService;
use Illuminate\Http\JsonResponse;

/**
 * PosOfflineStatusController - Statut offline pour UI
 *
 * Endpoint pour que UI détecte mode offline
 * et affiche notification utilisateur
 */
class PosOfflineStatusController
{
    public function __construct(
        protected PosOfflineService $offlineService
    ) {}

    /**
     * Obtenir statut offline
     *
     * GET /pos/offline-status
     */
    public function __invoke(): JsonResponse
    {
        $isOffline = $this->offlineService->isOffline();
        $status = $this->offlineService->getOfflineStatus();
        $queueCount = $this->offlineService->getOfflineQueueCount();

        return response()->json([
            'offline' => $isOffline,
            'reason' => $status['reason'] ?? null,
            'marked_at' => $status['marked_at'] ?? null,
            'queue_count' => $queueCount,
            'message' => $this->getHumanMessage($isOffline, $queueCount),
        ]);
    }

    /**
     * Message pour UI (lisible utilisateur)
     */
    private function getHumanMessage(bool $isOffline, int $queueCount): string
    {
        if (!$isOffline) {
            return '✅ Système online — Toutes opérations disponibles';
        }

        if ($queueCount === 0) {
            return '⚠️ Mode offline — Ventes en attente de reconnexion';
        }

        return "⚠️ Mode offline — {$queueCount} vente(s) en attente de sync";
    }
}

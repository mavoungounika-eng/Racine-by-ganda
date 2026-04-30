<?php

namespace App\Http\Middleware;

use App\Services\Pos\PosOfflineService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Middleware PosOfflineDetection
 *
 * Détecte si système est offline et ajoute info à la réponse
 *
 * Utilisation:
 *   Route::middleware(PosOfflineDetection::class)->prefix('pos')->group(...)
 */
class PosOfflineDetection
{
    public function __construct(
        protected PosOfflineService $offlineService
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Ajouter header offline si système est offline
        if ($this->offlineService->isOffline()) {
            $status = $this->offlineService->getOfflineStatus();

            $response->header('X-Pos-Offline', 'true');
            $response->header('X-Pos-Offline-Reason', $status['reason'] ?? 'Unknown');
            $response->header('X-Pos-Offline-Queue-Count', $this->offlineService->getOfflineQueueCount());

            Log::warning('POS request during offline mode', [
                'path' => $request->path(),
                'reason' => $status['reason'] ?? 'Unknown',
            ]);
        }

        return $response;
    }
}

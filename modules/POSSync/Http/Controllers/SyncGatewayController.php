<?php

namespace Modules\POSSync\Http\Controllers;

use App\Http\Responses\PosApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\POSSync\Models\PosSyncLog;
use Modules\POSSync\Services\DeviceAuthService;
use Modules\POSSync\Services\EventDispatcher;
use Modules\POSSync\Services\IdempotenceService;

class SyncGatewayController extends Controller
{
    public function __construct(
        protected DeviceAuthService $deviceAuthService,
        protected IdempotenceService $idempotenceService,
        protected EventDispatcher $eventDispatcher
    ) {}

    /**
     * Point d'entrée principal pour la synchronisation des événements POS
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncEvents(Request $request)
    {
        // 1. Device resolved by middleware when available (fallback to manual JWT validation)
        $device = $request->attributes->get('pos_device');
        if (!$device) {
            $token = $request->bearerToken();
            $device = $this->deviceAuthService->validateToken($token);
        }

        if (!$device) {
            return PosApiResponse::unauthorized('Invalid token');
        }

        // 2. Vérifier le statut de l'appareil (bloqué = rejeter)
        if ($device->status === 'blocked') {
            return PosApiResponse::error(
                'DEVICE_BLOCKED',
                'Device blocked',
                [
                    'blocked_reason' => $device->blocked_reason,
                    'contact_admin' => true,
                ],
                403
            );
        }

        // 3. Valider la structure du lot d'événements
        $validated = $request->validate([
            'machine_id' => 'required|uuid',
            'events' => 'required|array',
            'events.*.uuid' => 'required|uuid',
            'events.*.type' => 'required|string',
            'events.*.version' => 'required|integer|min:1', // 🔴 Version obligatoire
            'events.*.payload' => 'required|array',
            'events.*.signature' => 'required|string|size:64', // 🔴 Signature HMAC obligatoire
            'events.*.occurred_at' => 'required|integer',
        ]);

        $results = [];

        // 4. Traiter chaque événement
        foreach ($validated['events'] as $eventData) {
            try {
                // 🔴 CRITIQUE - Vérifier signature HMAC AVANT tout traitement
                $isValidSignature = $this->verifyHmacSignature(
                    $eventData,
                    $device->machine_secret
                );

                if (!$isValidSignature) {
                    // FRAUDE DÉTECTÉE - Bloquer la machine immédiatement
                    $this->deviceAuthService->blockDevice(
                        $device->machine_id,
                        'Invalid HMAC signature detected - Potential fraud'
                    );

                    $this->logSyncOperation(
                        $device->machine_id,
                        $eventData['uuid'],
                        'sync_rejected_fraud',
                        'Invalid signature - device blocked'
                    );

                    return PosApiResponse::error(
                        'INVALID_SIGNATURE',
                        'Security violation - device blocked',
                        ['device_status' => 'blocked'],
                        403
                    );
                }

                // Vérifier idempotence (machine_id + event_uuid)
                if ($this->idempotenceService->isProcessed($device->machine_id, $eventData['uuid'])) {
                    $results[] = [
                        'uuid' => $eventData['uuid'],
                        'success' => true,
                        'message' => 'Already processed (idempotent)'
                    ];

                    $this->logSyncOperation(
                        $device->machine_id,
                        $eventData['uuid'],
                        'sync_duplicate_ignored',
                        'Event already processed'
                    );

                    continue;
                }

                // Dispatcher vers le gestionnaire approprié
                $this->eventDispatcher->dispatch($eventData['type'], $eventData['payload']);

                // Marquer comme traité (idempotence)
                $this->idempotenceService->markProcessed(
                    $device->machine_id,
                    $eventData['uuid'],
                    $eventData
                );

                // Logger succès
                $this->logSyncOperation(
                    $device->machine_id,
                    $eventData['uuid'],
                    'sync_success',
                    null
                );

                $results[] = [
                    'uuid' => $eventData['uuid'],
                    'success' => true
                ];

            } catch (\Exception $e) {
                $this->logSyncOperation(
                    $device->machine_id,
                    $eventData['uuid'],
                    'sync_rejected',
                    $e->getMessage()
                );

                $results[] = [
                    'uuid' => $eventData['uuid'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Mettre à jour le timestamp de dernière sync
        $device->updateLastSync();

        // 5. Retourner les résultats du lot
        return PosApiResponse::success([
            'results' => $results,
            'device_status' => $device->status,
        ]);
    }

    /**
     * 🔴 MÉTHODE CRITIQUE - Vérification signature HMAC
     * 
     * @param array $eventData
     * @param string $machineSecret
     * @return bool
     */
    private function verifyHmacSignature(array $eventData, string $machineSecret): bool
    {
        $signature = $eventData['signature'];
        
        // Retirer signature pour recalcul
        $dataToSign = $eventData;
        unset($dataToSign['signature']);

        // Recalculer signature attendue
        $expectedSignature = hash_hmac(
            'sha256',
            json_encode($dataToSign),
            $machineSecret
        );

        // Comparaison sécurisée (timing attack safe)
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Logger une opération de sync (audit trail)
     * 
     * @param string $machineId
     * @param string $eventUuid
     * @param string $action
     * @param string|null $details
     * @return void
     */
    private function logSyncOperation(
        string $machineId,
        string $eventUuid,
        string $action,
        ?string $details
    ): void {
        PosSyncLog::create([
            'machine_id' => $machineId,
            'event_uuid' => $eventUuid,
            'action' => $action,
            'details' => $details,
            'created_at' => now(),
        ]);
    }

    /**
     * Enregistrer un nouveau device POS
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerDevice(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|uuid|unique:pos_devices,machine_id',
            'name' => 'required|string|max:255',
            'version' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        // Générer machine_secret cryptographiquement sécurisé
        $machineSecret = bin2hex(random_bytes(32)); // 64 caractères hex

        $device = \Modules\POSSync\Models\PosDevice::create([
            'machine_id' => $validated['machine_id'],
            'name' => $validated['name'],
            'machine_secret' => $machineSecret, // Sera chiffré automatiquement par le modèle
            'status' => 'pending', // Admin doit activer
            'version' => $validated['version'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        // Générer JWT
        $token = $this->deviceAuthService->generateToken($device->machine_id);

        return PosApiResponse::success([
            'device_id' => $device->id,
            'machine_id' => $device->machine_id,
            'machine_secret' => $machineSecret, // Retourné UNE SEULE FOIS
            'token' => $token,
            'status' => $device->status,
        ], 'Device registered. Awaiting admin activation.', 201);
    }

    /**
     * Renouveler le token JWT
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        /** @var \Modules\POSSync\Models\PosDevice|null $device */
        $device = $request->attributes->get('pos_device');
        $newToken = $device
            ? $this->deviceAuthService->generateToken($device->machine_id)
            : $this->deviceAuthService->refreshToken((string) $request->bearerToken());

        if (!$newToken) {
            return PosApiResponse::unauthorized('Token refresh failed');
        }

        return PosApiResponse::success([
            'token' => $newToken,
            'expires_at' => time() + (7 * 24 * 60 * 60),
        ]);
    }

    /**
     * Obtenir le statut du device
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeviceStatus(Request $request)
    {
        // Device resolved by middleware when available (fallback to manual JWT validation)
        $device = $request->attributes->get('pos_device');
        if (!$device) {
            $device = $this->deviceAuthService->validateToken($request->bearerToken());
        }

        if (!$device) {
            return PosApiResponse::unauthorized('Invalid token');
        }

        return PosApiResponse::success([
            'machine_id' => $device->machine_id,
            'name' => $device->name,
            'status' => $device->status,
            'last_sync_at' => $device->last_sync_at,
            'version' => $device->version,
        ]);
    }
}

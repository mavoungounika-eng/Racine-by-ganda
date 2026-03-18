<?php

namespace App\Http\Controllers\Pos;
use App\Models\PosSession;
use App\Services\Pos\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * PosSessionController - Gestion des sessions de caisse
 * 
 * INVARIANTS:
 * - Une session ouverte obligatoire pour vendre
 * - opening_cash requis à l'ouverture
 * - closing_cash requis pour fermeture
 */
class PosSessionController extends PosApiController
{
    public function __construct(
        protected PosSessionService $sessionService
    ) {}

    /**
     * Ouvrir une nouvelle session de caisse
     * 
     * POST /pos/sessions/open
     */
    public function open(Request $request): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');
        $userId = $request->posUserId ?? Auth::id();

        if (!$machineId) {
            $validated = $request->validate([
                'machine_id' => 'required|uuid',
                'opening_cash' => 'required|numeric|min:0',
            ]);
            $machineId = $validated['machine_id'];
        } else {
            $request->validate([
                'opening_cash' => 'required|numeric|min:0',
            ]);
            if (!Str::isUuid($machineId)) {
                return $this->error('INVALID_MACHINE_ID', 'machine_id must be a valid UUID');
            }
        }

        if (!$userId) {
            return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
        }

        try {
            $session = $this->sessionService->openSession(
                $machineId,
                $userId,
                (float) $request->input('opening_cash')
            );

            return $this->success([
                'session' => [
                    'id' => $session->id,
                    'machine_id' => $session->machine_id,
                    'opened_at' => $session->opened_at->toIso8601String(),
                    'opening_cash' => $session->opening_cash,
                    'status' => $session->status,
                ],
            ], 'Session de caisse ouverte', 201);
        } catch (\Exception $e) {
            return $this->error('SESSION_OPEN_FAILED', $e->getMessage(), null, 409);
        }
    }

    /**
     * Obtenir la session active d'une machine
     * 
     * GET /pos/sessions/current?machine_id={uuid}
     */
    public function current(Request $request): JsonResponse
    {
        $machineId = $request->machineId ?? $request->input('machine_id');
        if (!$machineId) {
            $validated = $request->validate([
                'machine_id' => 'required|uuid',
            ]);
            $machineId = $validated['machine_id'];
        } elseif (!Str::isUuid($machineId)) {
            return $this->error('INVALID_MACHINE_ID', 'machine_id must be a valid UUID');
        }

        $session = $this->sessionService->getOpenSession($machineId);

        if (!$session) {
            return $this->error('SESSION_NOT_FOUND', 'Aucune session ouverte', [
                'has_open_session' => false,
            ], 404);
        }

        return $this->success([
            'has_open_session' => true,
            'session' => [
                'id' => $session->id,
                'machine_id' => $session->machine_id,
                'opened_at' => $session->opened_at->toIso8601String(),
                'opened_by' => $session->opener?->name,
                'opening_cash' => $session->opening_cash,
                'status' => $session->status,
                'sales_count' => $session->sales()->count(),
                'sales_total' => $session->sales()->sum('total_amount'),
            ],
        ]);
    }

    /**
     * Préparer la clôture (obtenir données pour Z-Report)
     * 
     * GET /pos/sessions/{session}/prepare-close
     */
    public function prepareClose(PosSession $session): JsonResponse
    {
        if ($this->isMachineMismatch($session->machine_id)) {
            return $this->error('MACHINE_MISMATCH', 'Session does not belong to this device', null, 403);
        }

        if (!$session->canClose()) {
            return $this->error('SESSION_NOT_CLOSABLE', 'Cette session ne peut pas être clôturée');
        }

        try {
            $data = $this->sessionService->prepareClose($session);

            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error('SESSION_PRE_CLOSE_FAILED', $e->getMessage());
        }
    }

    /**
     * Clôturer la session
     * 
     * POST /pos/sessions/{session}/close
     */
    public function close(Request $request, PosSession $session): JsonResponse
    {
        $validated = $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($this->isMachineMismatch($session->machine_id)) {
            return $this->error('MACHINE_MISMATCH', 'Session does not belong to this device', null, 403);
        }

        $userId = $request->posUserId ?? Auth::id();
        if (!$userId) {
            return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
        }

        try {
            $closedSession = $this->sessionService->closeSession(
                $session,
                $validated['closing_cash'],
                $userId,
                $validated['notes'] ?? null
            );

            return $this->success([
                'session' => [
                    'id' => $closedSession->id,
                    'status' => $closedSession->status,
                    'opening_cash' => $closedSession->opening_cash,
                    'closing_cash' => $closedSession->closing_cash,
                    'expected_cash' => $closedSession->expected_cash,
                    'cash_difference' => $closedSession->cash_difference,
                    'closed_at' => $closedSession->closed_at->toIso8601String(),
                ],
                'z_report_url' => route('pos.sessions.z-report', $closedSession),
            ], 'Session clôturée avec succès');
        } catch (\Exception $e) {
            return $this->error('SESSION_CLOSE_FAILED', $e->getMessage());
        }
    }

    /**
     * Générer Z-Report (version simplifiée)
     * 
     * GET /pos/sessions/{session}/z-report
     */
    public function zReport(PosSession $session): JsonResponse
    {
        if ($this->isMachineMismatch($session->machine_id)) {
            return $this->error('MACHINE_MISMATCH', 'Session does not belong to this device', null, 403);
        }

        if (!$session->isClosed()) {
            return $this->error('Z_REPORT_UNAVAILABLE', 'Z-Report disponible uniquement pour les sessions fermées');
        }

        $sales = $session->sales()->with('payments')->get();
        $movements = $session->cashMovements;

        return $this->success([
            'z_report' => [
                'session_id' => $session->id,
                'machine_id' => $session->machine_id,
                'opened_by' => $session->opener?->name,
                'closed_by' => $session->closer?->name,
                'opened_at' => $session->opened_at->toIso8601String(),
                'closed_at' => $session->closed_at->toIso8601String(),
                'opening_cash' => $session->opening_cash,
                'closing_cash' => $session->closing_cash,
                'expected_cash' => $session->expected_cash,
                'cash_difference' => $session->cash_difference,
                'summary' => [
                    'total_sales' => $sales->count(),
                    'total_amount' => $sales->sum('total_amount'),
                    'cash_sales' => $sales->where('payment_method', 'cash')->count(),
                    'cash_amount' => $sales->where('payment_method', 'cash')->sum('total_amount'),
                    'card_sales' => $sales->where('payment_method', 'card')->count(),
                    'card_amount' => $sales->where('payment_method', 'card')->sum('total_amount'),
                    'mobile_sales' => $sales->where('payment_method', 'mobile_money')->count(),
                    'mobile_amount' => $sales->where('payment_method', 'mobile_money')->sum('total_amount'),
                ],
                'movements' => $movements->map(fn($m) => [
                    'type' => $m->type,
                    'amount' => $m->amount,
                    'direction' => $m->direction,
                    'reason' => $m->reason,
                    'created_at' => $m->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Créer un ajustement cash
     * 
     * POST /pos/sessions/{session}/adjustments
     */
    public function createAdjustment(Request $request, PosSession $session): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'direction' => 'required|in:in,out',
            'reason' => 'required|string|max:500',
        ]);

        if ($this->isMachineMismatch($session->machine_id)) {
            return $this->error('MACHINE_MISMATCH', 'Session does not belong to this device', null, 403);
        }

        $userId = $request->posUserId ?? Auth::id();
        if (!$userId) {
            return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
        }

        try {
            $movement = $this->sessionService->createAdjustment(
                $session,
                $validated['amount'],
                $validated['direction'],
                $validated['reason'],
                $userId
            );

            return $this->success([
                'movement' => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'amount' => $movement->amount,
                    'direction' => $movement->direction,
                    'reason' => $movement->reason,
                ],
            ], 'Ajustement enregistré', 201);
        } catch (\Exception $e) {
            return $this->error('ADJUSTMENT_FAILED', $e->getMessage());
        }
    }

    private function isMachineMismatch(string $machineId): bool
    {
        $requestMachineId = request()->machineId ?? null;

        return $requestMachineId !== null && $requestMachineId !== $machineId;
    }
}

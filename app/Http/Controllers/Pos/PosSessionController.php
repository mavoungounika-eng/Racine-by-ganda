<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSession;
use App\Services\Pos\PosSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PosSessionController - Gestion des sessions de caisse
 * 
 * INVARIANTS:
 * - Une session ouverte obligatoire pour vendre
 * - opening_cash requis à l'ouverture
 * - closing_cash requis pour fermeture
 */
class PosSessionController extends Controller
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
        $validated = $request->validate([
            'machine_id' => 'required|uuid',
            'opening_cash' => 'required|numeric|min:0',
        ]);

        try {
            $session = $this->sessionService->openSession(
                $validated['machine_id'],
                Auth::id(),
                $validated['opening_cash']
            );

            return response()->json([
                'success' => true,
                'message' => 'Session de caisse ouverte',
                'session' => [
                    'id' => $session->id,
                    'machine_id' => $session->machine_id,
                    'opened_at' => $session->opened_at->toIso8601String(),
                    'opening_cash' => $session->opening_cash,
                    'status' => $session->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Obtenir la session active d'une machine
     * 
     * GET /pos/sessions/current?machine_id={uuid}
     */
    public function current(Request $request): JsonResponse
    {
        $request->validate([
            'machine_id' => 'required|uuid',
        ]);

        $session = $this->sessionService->getOpenSession($request->machine_id);

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune session ouverte',
                'has_open_session' => false,
            ], 404);
        }

        return response()->json([
            'success' => true,
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
        if (!$session->canClose()) {
            return response()->json([
                'success' => false,
                'message' => 'Cette session ne peut pas être clôturée',
            ], 400);
        }

        try {
            $data = $this->sessionService->prepareClose($session);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
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

        try {
            $closedSession = $this->sessionService->closeSession(
                $session,
                $validated['closing_cash'],
                Auth::id(),
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Session clôturée avec succès',
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
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Générer Z-Report (version simplifiée)
     * 
     * GET /pos/sessions/{session}/z-report
     */
    public function zReport(PosSession $session): JsonResponse
    {
        if (!$session->isClosed()) {
            return response()->json([
                'success' => false,
                'message' => 'Z-Report disponible uniquement pour les sessions fermées',
            ], 400);
        }

        $sales = $session->sales()->with('payments')->get();
        $movements = $session->cashMovements;

        return response()->json([
            'success' => true,
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

        try {
            $movement = $this->sessionService->createAdjustment(
                $session,
                $validated['amount'],
                $validated['direction'],
                $validated['reason'],
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Ajustement enregistré',
                'movement' => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'amount' => $movement->amount,
                    'direction' => $movement->direction,
                    'reason' => $movement->reason,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}

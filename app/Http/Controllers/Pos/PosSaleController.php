<?php

namespace App\Http\Controllers\Pos;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Services\Pos\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * PosSaleController - Création et gestion des ventes POS
 * 
 * INVARIANTS:
 * - Pas de vente sans session ouverte
 * - Cash reste 'pending' jusqu'à clôture
 * - POS ne déclenche JAMAIS PaymentRecorded
 */
class PosSaleController extends PosApiController
{
    public function __construct(
        protected PosSaleService $saleService
    ) {}

    /**
     * Créer une nouvelle vente POS
     * 
     * POST /pos/sales
     */
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'machine_id' => 'required|uuid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_money',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'coupon_code' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
        ];

        if ($request->machineId !== null) {
            $rules['machine_id'] = 'sometimes|uuid';
        }

        $validated = $request->validate($rules);

        try {
            $machineId = $request->machineId ?? $validated['machine_id'];
            if (!Str::isUuid($machineId)) {
                return $this->error('INVALID_MACHINE_ID', 'machine_id must be a valid UUID');
            }

            $userId = $request->posUserId ?? $request->posOperator?->id ?? Auth::id();
            if (!$userId) {
                return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
            }

            $idempotencyKey = $request->header('X-Idempotency-Key');
            $sale = $this->saleService->createSale(
                $machineId,
                $validated['items'],
                $validated['payment_method'],
                $userId,
                [
                    'discount_percent' => $validated['discount_percent'] ?? null,
                    'coupon_code' => $validated['coupon_code'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_email' => $validated['customer_email'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                ],
                $idempotencyKey
            );

            return $this->success([
                'sale' => [
                    'id' => $sale->id,
                    'uuid' => $sale->uuid,
                    'order_id' => $sale->order_id,
                    'session_id' => $sale->session_id,
                    'total_amount' => $sale->total_amount,
                    'payment_method' => $sale->payment_method,
                    'status' => $sale->status,
                    'payment_status' => $sale->payments->first()?->status ?? 'pending',
                ],
                'awaiting_confirmation' => $validated['payment_method'] !== 'cash',
            ], 'Vente créée avec succès', 201);
        } catch (\Exception $e) {
            return $this->error('SALE_CREATION_FAILED', $e->getMessage());
        }
    }

    /**
     * Obtenir les détails d'une vente
     * 
     * GET /pos/sales/{sale}
     */
    public function show(PosSale $sale): JsonResponse
    {
        if ($this->isMachineMismatch($sale->machine_id)) {
            return $this->error('MACHINE_MISMATCH', 'Sale does not belong to this device', null, 403);
        }

        $sale->load(['order.items.product', 'payments', 'session']);

        return $this->success([
            'sale' => [
                'id' => $sale->id,
                'uuid' => $sale->uuid,
                'order_id' => $sale->order_id,
                'session_id' => $sale->session_id,
                'machine_id' => $sale->machine_id,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'created_at' => $sale->created_at->toIso8601String(),
                'finalized_at' => $sale->finalized_at?->toIso8601String(),
                'order' => [
                    'id' => $sale->order->id,
                    'order_number' => $sale->order->order_number,
                    'items' => $sale->order->items->map(fn($item) => [
                        'product_id' => $item->product_id,
                        'product_title' => $item->product?->title,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ]),
                ],
                'payments' => $sale->payments->map(fn($p) => [
                    'id' => $p->id,
                    'method' => $p->method,
                    'amount' => $p->amount,
                    'status' => $p->status,
                    'confirmed_at' => $p->confirmed_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Annuler une vente
     * 
     * POST /pos/sales/{sale}/cancel
     */
    public function cancel(Request $request, PosSale $sale): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            if ($this->isMachineMismatch($sale->machine_id)) {
                return $this->error('MACHINE_MISMATCH', 'Sale does not belong to this device', null, 403);
            }

            $userId = $request->posUserId ?? $request->posOperator?->id ?? Auth::id();
            if (!$userId) {
                return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
            }

            $cancelledSale = $this->saleService->cancelSale(
                $sale,
                $userId,
                $validated['reason']
            );

            return $this->success([
                'sale' => [
                    'id' => $cancelledSale->id,
                    'status' => $cancelledSale->status,
                    'cancelled_at' => $cancelledSale->cancelled_at->toIso8601String(),
                    'cancellation_reason' => $cancelledSale->cancellation_reason,
                ],
            ], 'Vente annulée');
        } catch (\Exception $e) {
            return $this->error('SALE_CANCEL_FAILED', $e->getMessage());
        }
    }

    /**
     * Liste des ventes d'une session
     * 
     * GET /pos/sessions/{session_id}/sales
     */
    public function forSession(Request $request, int $sessionId): JsonResponse
    {
        if ($request->machineId !== null) {
            $session = PosSession::find($sessionId);
            if (!$session) {
                return $this->error('SESSION_NOT_FOUND', 'Session not found', null, 404);
            }
            if ($this->isMachineMismatch($session->machine_id)) {
                return $this->error('MACHINE_MISMATCH', 'Session does not belong to this device', null, 403);
            }
        }

        $sales = PosSale::forSession($sessionId)
            ->with('payments', 'order.items.product')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success([
            'sales' => $sales->map(fn($sale) => [
                'id' => $sale->id,
                'uuid' => $sale->uuid,
                'order_id' => $sale->order_id,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'payment_status' => $sale->payments->first()?->status,
                'created_at' => $sale->created_at->toIso8601String(),
                'items' => $sale->order?->items?->map(fn($item) => [
                    'id' => $item->id,
                    'product_name' => $item->product?->title ?? 'Produit supprimé',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->price,
                    'subtotal' => $item->subtotal,
                ]) ?? [],
            ]),
            'total_count' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
        ]);
    }

    private function isMachineMismatch(string $machineId): bool
    {
        $requestMachineId = request()->machineId ?? null;

        return $requestMachineId !== null && $requestMachineId !== $machineId;
    }
}

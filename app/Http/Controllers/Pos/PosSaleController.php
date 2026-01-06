<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Services\Pos\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PosSaleController - Création et gestion des ventes POS
 * 
 * INVARIANTS:
 * - Pas de vente sans session ouverte
 * - Cash reste 'pending' jusqu'à clôture
 * - POS ne déclenche JAMAIS PaymentRecorded
 */
class PosSaleController extends Controller
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
        $validated = $request->validate([
            'machine_id' => 'required|uuid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,mobile_money',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:50',
        ]);

        try {
            $sale = $this->saleService->createSale(
                $validated['machine_id'],
                $validated['items'],
                $validated['payment_method'],
                Auth::id(),
                [
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_email' => $validated['customer_email'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Vente créée avec succès',
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
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtenir les détails d'une vente
     * 
     * GET /pos/sales/{sale}
     */
    public function show(PosSale $sale): JsonResponse
    {
        $sale->load(['order.items.product', 'payments', 'session']);

        return response()->json([
            'success' => true,
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
            $cancelledSale = $this->saleService->cancelSale(
                $sale,
                Auth::id(),
                $validated['reason']
            );

            return response()->json([
                'success' => true,
                'message' => 'Vente annulée',
                'sale' => [
                    'id' => $cancelledSale->id,
                    'status' => $cancelledSale->status,
                    'cancelled_at' => $cancelledSale->cancelled_at->toIso8601String(),
                    'cancellation_reason' => $cancelledSale->cancellation_reason,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Liste des ventes d'une session
     * 
     * GET /pos/sessions/{session_id}/sales
     */
    public function forSession(Request $request, int $sessionId): JsonResponse
    {
        $sales = PosSale::forSession($sessionId)
            ->with('payments')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'sales' => $sales->map(fn($sale) => [
                'id' => $sale->id,
                'uuid' => $sale->uuid,
                'order_id' => $sale->order_id,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method,
                'status' => $sale->status,
                'payment_status' => $sale->payments->first()?->status,
                'created_at' => $sale->created_at->toIso8601String(),
            ]),
            'total_count' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
        ]);
    }
}

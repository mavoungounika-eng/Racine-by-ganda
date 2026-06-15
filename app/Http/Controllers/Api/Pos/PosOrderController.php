<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StorePosOrderRequest;
use App\Models\Order;
use App\Services\Pos\PosConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PosOrderController — ventes de l'API POS Connect (Electron).
 *
 * POST /api/pos/v1/orders → création d'une vente (stock décrémenté en transaction)
 * GET  /api/pos/v1/orders → historique paginé des ventes POS du créateur
 */
class PosOrderController extends Controller
{
    public function __construct(
        protected PosConnectService $posConnect,
    ) {
    }

    public function store(StorePosOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->posConnect->createOrder($request->user(), $request->validated());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // Idempotence : si l'offline_id était déjà synchronisé, l'ordre existant
        // est renvoyé (200) au lieu d'un doublon (201).
        $alreadyExisted = ! $order->wasRecentlyCreated;

        return response()->json([
            'success' => true,
            'data'    => $this->formatOrder($order->loadMissing('items')),
        ], $alreadyExisted ? 200 : 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('creator_id', $request->user()->id)
            ->where('source', PosConnectService::SOURCE_POS)
            ->with('items.product:id,title')
            ->latest('id')
            ->paginate(20);

        $orders->getCollection()->transform(fn (Order $order) => $this->formatOrder($order));

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatOrder(Order $order): array
    {
        return [
            'id'             => $order->id,
            'order_number'   => $order->order_number,
            'offline_id'     => $order->offline_id,
            'status'         => $order->status,
            'payment_status' => $order->payment_status,
            'payment_method' => $order->payment_method,
            'total'          => (float) $order->total_amount,
            'currency'       => $order->currency,
            'created_at'     => $order->created_at?->toIso8601String(),
            'items'          => $order->items->map(fn ($item) => [
                'product_id'   => $item->product_id,
                'product_name' => $item->relationLoaded('product') ? $item->product?->title : null,
                'quantity'     => (int) $item->quantity,
                'price'        => (float) $item->price,
            ])->values(),
        ];
    }
}

<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosPayment;
use App\Services\Pos\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PosPaymentController - Confirmation des paiements POS
 * 
 * INVARIANTS:
 * - Cash confirmé uniquement à la clôture session (pas via ce controller)
 * - Card confirmé via TPE (endpoint confirm)
 * - Mobile confirmé via Webhook (endpoint confirmMobile)
 */
class PosPaymentController extends Controller
{
    public function __construct(
        protected PosSaleService $saleService
    ) {}

    /**
     * Confirmer un paiement carte (après validation TPE)
     * 
     * POST /pos/payments/{payment}/confirm-card
     */
    public function confirmCard(Request $request, PosPayment $payment): JsonResponse
    {
        $validated = $request->validate([
            'transaction_id' => 'nullable|string|max:255',
            'receipt_number' => 'nullable|string|max:255',
        ]);

        try {
            $confirmedPayment = $this->saleService->confirmCardPayment(
                $payment,
                Auth::id(),
                $validated['transaction_id'] ?? null,
                $validated['receipt_number'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement carte confirmé',
                'payment' => [
                    'id' => $confirmedPayment->id,
                    'method' => $confirmedPayment->method,
                    'amount' => $confirmedPayment->amount,
                    'status' => $confirmedPayment->status,
                    'confirmed_at' => $confirmedPayment->confirmed_at->toIso8601String(),
                    'external_reference' => $confirmedPayment->external_reference,
                ],
                'sale_status' => $confirmedPayment->sale->status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Webhook pour confirmation paiement mobile (Monetbil callback)
     * 
     * POST /pos/payments/webhook/mobile
     * 
     * Note: Ce endpoint sera appelé par Monetbil, pas par l'app POS
     */
    public function webhookMobile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => 'required|integer|exists:pos_payments,id',
            'transaction_id' => 'required|string',
            'status' => 'required|in:success,failed',
        ]);

        $payment = PosPayment::findOrFail($validated['payment_id']);

        if ($validated['status'] !== 'success') {
            $payment->cancel();
            
            return response()->json([
                'success' => false,
                'message' => 'Payment failed',
            ]);
        }

        try {
            $this->saleService->confirmMobilePayment(
                $payment,
                $validated['transaction_id'],
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment confirmed',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Obtenir le statut d'un paiement
     * 
     * GET /pos/payments/{payment}/status
     */
    public function status(PosPayment $payment): JsonResponse
    {
        $payment->load('sale');

        return response()->json([
            'success' => true,
            'payment' => [
                'id' => $payment->id,
                'method' => $payment->method,
                'amount' => $payment->amount,
                'status' => $payment->status,
                'confirmed_at' => $payment->confirmed_at?->toIso8601String(),
                'external_reference' => $payment->external_reference,
            ],
            'sale' => [
                'id' => $payment->sale->id,
                'status' => $payment->sale->status,
            ],
        ]);
    }
}

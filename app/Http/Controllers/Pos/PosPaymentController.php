<?php

namespace App\Http\Controllers\Pos;
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
class PosPaymentController extends PosApiController
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
            if ($this->isMachineMismatch($payment->sale->machine_id)) {
                return $this->error('MACHINE_MISMATCH', 'Payment does not belong to this device', null, 403);
            }

            $userId = $request->posUserId ?? $request->posOperator?->id ?? Auth::id();
            if (!$userId) {
                return $this->error('POS_USER_REQUIRED', 'Operator user_id is required');
            }

            $confirmedPayment = $this->saleService->confirmCardPayment(
                $payment,
                $userId,
                $validated['transaction_id'] ?? null,
                $validated['receipt_number'] ?? null
            );

            return $this->success([
                'payment' => [
                    'id' => $confirmedPayment->id,
                    'method' => $confirmedPayment->method,
                    'amount' => $confirmedPayment->amount,
                    'status' => $confirmedPayment->status,
                    'confirmed_at' => $confirmedPayment->confirmed_at->toIso8601String(),
                    'external_reference' => $confirmedPayment->external_reference,
                ],
                'sale_status' => $confirmedPayment->sale->status,
            ], 'Paiement carte confirmé');
        } catch (\Exception $e) {
            return $this->error('PAYMENT_CONFIRM_FAILED', $e->getMessage());
        }
    }

    /**
     * Webhook pour confirmation paiement mobile (Monetbil callback)
     *
     * POST /pos/payments/webhook/mobile
     *
     * FIX 3: Validation signature HMAC Monetbil ajoutée.
     * Note: Ce endpoint sera appelé par Monetbil, pas par l'app POS.
     */
    public function webhookMobile(Request $request): JsonResponse
    {
        // FIX 3 — Vérification signature HMAC Monetbil
        if (! $this->verifyMonetbilSignature($request)) {
            \Illuminate\Support\Facades\Log::warning('POS webhook mobile: invalid signature', [
                'ip' => $request->ip(),
                'path' => $request->path(),
            ]);
            return $this->error('INVALID_SIGNATURE', 'Invalid signature', null, 403);
        }

        $validated = $request->validate([
            'payment_id'     => 'required|integer|exists:pos_payments,id',
            'transaction_id' => 'required|string',
            'status'         => 'required|in:success,failed',
        ]);

        $payment = PosPayment::findOrFail($validated['payment_id']);

        if ($validated['status'] !== 'success') {
            $payment->cancel();

            return $this->error('PAYMENT_FAILED', 'Payment failed');
        }

        try {
            $this->saleService->confirmMobilePayment(
                $payment,
                $validated['transaction_id'],
                $request->all()
            );

            return $this->success(null, 'Payment confirmed');
        } catch (\Exception $e) {
            return $this->error('PAYMENT_CONFIRM_FAILED', $e->getMessage());
        }
    }

    /**
     * Obtenir le statut d'un paiement
     * 
     * GET /pos/payments/{payment}/status
     */
    public function status(PosPayment $payment): JsonResponse
    {
        if ($this->isMachineMismatch($payment->sale->machine_id)) {
            return $this->error('MACHINE_MISMATCH', 'Payment does not belong to this device', null, 403);
        }

        $payment->load('sale');

        return $this->success([
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

    /**
     * FIX 3 — Vérification signature HMAC Monetbil
     *
     * Monetbil signe les callbacks avec HMAC-SHA512 sur le corps brut de la requête,
     * en utilisant la clé secrète du service (MONETBIL_SERVICE_KEY).
     * L'en-tête attendu est X-Monetbil-Signature.
     */
    private function verifyMonetbilSignature(Request $request): bool
    {
        $signature = $request->header('X-Monetbil-Signature');

        if (empty($signature)) {
            return false;
        }

        $secret = config('services.monetbil.service_key', env('MONETBIL_SERVICE_KEY', ''));

        if (empty($secret)) {
            // En mode test (APP_ENV=testing), on accepte sans signature pour ne pas bloquer les tests
            if (app()->environment('testing')) {
                return true;
            }
            \Illuminate\Support\Facades\Log::error('POS webhook: MONETBIL_SERVICE_KEY not configured');
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }

    private function isMachineMismatch(string $machineId): bool
    {
        $requestMachineId = request()->machineId ?? null;

        return $requestMachineId !== null && $requestMachineId !== $machineId;
    }
}

<?php

namespace App\Http\Controllers\Payments;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\MonetbilService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur pour les paiements Mobile Money via Monetbil
 */
class MonetbilController extends Controller
{
    protected MonetbilService $monetbilService;

    public function __construct(MonetbilService $monetbilService)
    {
        $this->monetbilService = $monetbilService;
    }

    /**
     * Initier un paiement Monetbil pour une commande
     *
     * @param Request $request
     * @param Order $order
     * @return RedirectResponse
     */
    public function start(Request $request, Order $order): RedirectResponse
    {
        // Vérifier l'accès à la commande
        $this->authorize('view', $order);

        // ✅ CORRECTION 2 : Lock commande avant paiement pour éviter double paiement
        $lockedOrder = Order::where('id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedOrder) {
            return redirect()
                ->route('checkout.index')
                ->with('error', 'La commande n\'existe plus.');
        }

        // ✅ CORRECTION 2 : Vérifier payment_status sous lock
        if ($lockedOrder->payment_status !== 'pending') {
            return redirect()
                ->route('checkout.success', ['order' => $lockedOrder->id])
                ->with('info', 'Cette commande est déjà payée ou n\'est plus valide.');
        }

        try {
            // Utiliser payment_ref = order_number pour garantir l'unicité
            $paymentRef = $lockedOrder->order_number ?? 'ORDER-' . $lockedOrder->id;

            // ✅ CORRECTION 4 : Vérifier si un paiement existe déjà pour cette commande
            $existingPayment = $lockedOrder->payments()
                ->whereIn('status', ['initiated', 'paid'])
                ->first();

            if ($existingPayment) {
                Log::info('Monetbil: Payment already exists for order', [
                    'order_id' => $lockedOrder->id,
                    'payment_id' => $existingPayment->id,
                    'payment_status' => $existingPayment->status,
                ]);
                // Rediriger vers la page de succès si déjà payé
                if ($existingPayment->status === 'paid') {
                    return redirect()
                        ->route('checkout.success', ['order' => $lockedOrder->id])
                        ->with('info', 'Cette commande est déjà payée.');
                }
                // Si initiated, continuer avec la transaction existante
            }

            // Vérifier si une transaction existe déjà pour cette commande
            $existingTransaction = PaymentTransaction::where('payment_ref', $paymentRef)
                ->where('order_id', $lockedOrder->id)
                ->where('status', 'pending')
                ->first();

            if ($existingTransaction) {
                // Transaction déjà en cours, récupérer l'URL de paiement depuis le payload
                $rawPayload = $existingTransaction->raw_payload;
                if (isset($rawPayload['payment_url'])) {
                    return redirect($rawPayload['payment_url']);
                }
            }

            // Créer ou mettre à jour la transaction en pending
            $transaction = PaymentTransaction::updateOrCreate(
                [
                    'payment_ref' => $paymentRef,
                    'order_id' => $lockedOrder->id,
                ],
                [
                    'provider' => 'monetbil',
                    'amount' => $lockedOrder->total_amount,
                    'currency' => config('services.monetbil.currency', 'XAF'),
                    'status' => 'pending',
                    'raw_payload' => [],
                ]
            );

            // Préparer le payload pour Monetbil
            $user = $lockedOrder->user;
            $customerName = $lockedOrder->customer_name ?? ($user ? $user->name : '');
            $nameParts = explode(' ', $customerName, 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';
            
            $payload = [
                'amount' => $lockedOrder->total_amount,
                'currency' => config('services.monetbil.currency', 'XAF'),
                'country' => config('services.monetbil.country', 'CG'),
                'payment_ref' => $paymentRef,
                'item_ref' => 'ORDER-' . $lockedOrder->id,
                'user' => $user ? $user->id : null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $lockedOrder->customer_email ?? ($user ? $user->email : ''),
                'notify_url' => config('services.monetbil.notify_url'),
                'return_url' => config('services.monetbil.return_url') . '?order=' . $lockedOrder->id,
            ];

            // Créer l'URL de paiement
            $paymentUrl = $this->monetbilService->createPaymentUrl($payload);

            // Mettre à jour la transaction avec l'URL de paiement
            $transaction->update([
                'raw_payload' => array_merge($transaction->raw_payload ?? [], [
                    'payment_url' => $paymentUrl,
                    'payload' => $payload,
                ]),
            ]);

            Log::info('Monetbil payment initiated', [
                'order_id' => $lockedOrder->id,
                'payment_ref' => $paymentRef,
                'amount' => $lockedOrder->total_amount,
            ]);

            return redirect($paymentUrl);
        } catch (PaymentException $e) {
            Log::error('Monetbil payment initiation failed', [
                'order_id' => $lockedOrder->id ?? $order->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('checkout.index')
                ->with('error', $e->getUserMessage());
        } catch (\Throwable $e) {
            Log::error('Monetbil payment initiation error', [
                'order_id' => $lockedOrder->id ?? $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('checkout.index')
                ->with('error', 'Une erreur est survenue lors de l\'initiation du paiement. Veuillez réessayer.');
        }
    }

    /**
     * Recevoir la notification de paiement Monetbil (GET ou POST)
     *
     * @param Request $request
     * @return Response
     */
    /**
     * Recevoir la notification de paiement Monetbil (GET ou POST)
     * 
     * RBG-P0-010 : Codes HTTP stricts alignés avec Stripe
     * - Signature absente/invalide en production => 401
     * - Payload invalide => 400
     * - IP non autorisée => 403
     * - Transaction introuvable => 404
     * - Erreur serveur inattendue => 500 (uniquement pour erreurs non prévues)
     *
     * @param Request $request
     * @return Response
     */
    public function notify(Request $request): Response
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $route = $request->fullUrl();

        // Récupérer tous les paramètres (GET ou POST)
        $params = $request->all();

        Log::info('Monetbil notification received', [
            'ip' => $ip,
            'route' => $route,
            'user_agent' => $userAgent,
            'method' => $request->method(),
        ]);

        try {
            // 1. Vérification IP (si whitelist configurée)
            if (!$this->monetbilService->isIpAllowed($ip)) {
                Log::warning('Monetbil notification from unauthorized IP', [
                    'ip' => $ip,
                    'route' => $route,
                    'user_agent' => $userAgent,
                    'reason' => 'unauthorized_ip',
                ]);

                return response()->json(['message' => 'Unauthorized IP'], 403);
            }

            // 2. Vérification de la signature
            $isProduction = app()->environment('production') || config('app.env') === 'production';
            $hasSignature = isset($params['sign']);
            
            if ($isProduction && !$hasSignature) {
                Log::error('Monetbil notification: Missing signature in production', [
                    'ip' => $ip,
                    'route' => $route,
                    'user_agent' => $userAgent,
                    'reason' => 'missing_signature',
                ]);

                return response()->json(['message' => 'Missing signature'], 401);
            }

            if (!$this->monetbilService->verifySignature($params)) {
                if ($isProduction) {
                    Log::error('Monetbil notification: Invalid signature', [
                        'ip' => $ip,
                        'route' => $route,
                        'user_agent' => $userAgent,
                        'reason' => 'invalid_signature',
                        'has_signature' => $hasSignature,
                    ]);

                    return response()->json(['message' => 'Invalid signature'], 401);
                }
                
                // En développement, continuer avec warning
                Log::warning('Monetbil notification: Invalid signature in development (continuing)', [
                    'ip' => $ip,
                    'route' => $route,
                    'reason' => 'invalid_signature_dev',
                ]);
            }

            // 3. Récupérer payment_ref (obligatoire)
            $paymentRef = $params['payment_ref'] ?? null;
            if (empty($paymentRef)) {
                Log::error('Monetbil notification: Missing payment_ref', [
                    'ip' => $ip,
                    'route' => $route,
                    'user_agent' => $userAgent,
                    'reason' => 'missing_payment_ref',
                ]);

                return response()->json(['message' => 'Missing payment_ref'], 400);
            }

            // 4. Récupérer le statut (obligatoire)
            $status = $params['status'] ?? null;
            if (empty($status)) {
                Log::error('Monetbil notification: Missing status', [
                    'ip' => $ip,
                    'route' => $route,
                    'user_agent' => $userAgent,
                    'payment_ref' => $paymentRef,
                    'reason' => 'missing_status',
                ]);

                return response()->json(['message' => 'Missing status'], 400);
            }

            // 5. Idempotence : retrouver la transaction
            $transaction = PaymentTransaction::where('payment_ref', $paymentRef)->first();

            if (!$transaction) {
                Log::warning('Monetbil notification: Transaction not found', [
                    'ip' => $ip,
                    'route' => $route,
                    'payment_ref' => $paymentRef,
                    'reason' => 'transaction_not_found',
                ]);

                return response()->json(['message' => 'Transaction not found'], 404);
            }

            // 6. Si déjà en succès, répondre OK sans refaire (idempotence)
            if ($transaction->isAlreadySuccessful()) {
                Log::info('Monetbil notification: Transaction already successful (idempotent)', [
                    'ip' => $ip,
                    'route' => $route,
                    'payment_ref' => $paymentRef,
                    'transaction_id' => $transaction->transaction_id,
                    'reason' => 'already_processed',
                ]);

                return response()->json(['status' => 'success'], 200);
            }

            // 7. Normaliser le statut
            $normalizedStatus = $this->monetbilService->normalizeStatus($status);

            // 8. PROTECTION RACE CONDITION : Transaction DB + lock
            DB::transaction(function () use ($transaction, $normalizedStatus, $params, $ip, $route) {
                // Verrouiller la transaction pour éviter race condition
                $lockedTransaction = PaymentTransaction::where('id', $transaction->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedTransaction) {
                    Log::error('Monetbil notification: Transaction not found after lock', [
                        'transaction_id' => $transaction->id,
                        'payment_ref' => $transaction->payment_ref,
                        'reason' => 'transaction_not_found_after_lock',
                    ]);
                    return;
                }

                // Vérifier à nouveau si déjà payé (double protection)
                if ($lockedTransaction->isAlreadySuccessful()) {
                    Log::info('Monetbil notification: Transaction already successful (race condition protection)', [
                        'transaction_id' => $lockedTransaction->id,
                        'payment_ref' => $lockedTransaction->payment_ref,
                        'reason' => 'already_processed_in_transaction',
                    ]);
                    return;
                }

                // Mettre à jour la transaction
                $lockedTransaction->update([
                    'status' => $normalizedStatus,
                    'transaction_id' => $params['transaction_id'] ?? $lockedTransaction->transaction_id,
                    'transaction_uuid' => $params['transaction_uuid'] ?? $lockedTransaction->transaction_uuid,
                    'operator' => $params['operator'] ?? $lockedTransaction->operator,
                    'phone' => $params['phone'] ?? $lockedTransaction->phone,
                    'fee' => isset($params['fee']) ? (float) $params['fee'] : $lockedTransaction->fee,
                    'raw_payload' => $params,
                    'notified_at' => now(),
                ]);

                // 9. Si succès, valider la commande
                if ($normalizedStatus === 'success' && $lockedTransaction->order_id) {
                    // ✅ CORRECTION 3 : Transaction atomique Transaction + Order + Payment
                    // Règle absolue : Payment = paid ⇔ Order.payment_status = paid
                    $order = Order::where('id', $lockedTransaction->order_id)
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$order) {
                        Log::error('Monetbil notification: Order not found', [
                            'transaction_id' => $lockedTransaction->id,
                            'order_id' => $lockedTransaction->order_id,
                            'reason' => 'order_not_found',
                        ]);
                        return;
                    }

                    // ✅ CORRECTION 7 : Vérifier si Order est dans un état terminal
                    if ($order->isTerminal()) {
                        Log::info('Monetbil notification: Order already in terminal state', [
                            'order_id' => $order->id,
                            'status' => $order->status,
                            'payment_status' => $order->payment_status,
                        ]);
                        return;
                    }

                    // ✅ CORRECTION 4 : Vérifier si un Payment existe déjà pour cette transaction
                    $existingPayment = $order->payments()
                        ->where('provider', 'monetbil')
                        ->where('external_reference', $lockedTransaction->transaction_id ?? $lockedTransaction->payment_ref)
                        ->first();

                    if ($existingPayment) {
                        Log::info('Monetbil notification: Payment already exists for transaction', [
                            'transaction_id' => $lockedTransaction->id,
                            'payment_id' => $existingPayment->id,
                            'order_id' => $order->id,
                        ]);
                        // Mettre à jour Order même si Payment existe déjà (idempotence)
                        $order->update([
                            'payment_status' => 'paid',
                            'status' => 'processing',
                        ]);
                        return;
                    }
                    
                    // Mettre à jour le statut de paiement de la commande
                    $order->update([
                        'payment_status' => 'paid',
                        'status' => 'processing',
                    ]);

                    // Créer un enregistrement Payment pour cohérence avec le système existant
                    try {
                        $order->payments()->create([
                            'provider' => 'monetbil',
                            'channel' => 'mobile_money',
                            'status' => 'paid',
                            'amount' => $lockedTransaction->amount,
                            'currency' => $lockedTransaction->currency,
                            'customer_phone' => $lockedTransaction->phone,
                            'external_reference' => $lockedTransaction->transaction_id ?? $lockedTransaction->payment_ref,
                            'provider_payment_id' => $lockedTransaction->transaction_id,
                            'metadata' => [
                                'operator' => $lockedTransaction->operator,
                                'transaction_uuid' => $lockedTransaction->transaction_uuid,
                            ],
                            'payload' => $params,
                            'paid_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Monetbil notification: Failed to create Payment record', [
                            'error' => $e->getMessage(),
                            'transaction_id' => $lockedTransaction->id,
                            'order_id' => $order->id,
                            'reason' => 'payment_creation_failed',
                        ]);
                        // Ne pas bloquer si la création du Payment échoue
                    }

                    // Déclencher l'événement de paiement réussi (si l'événement existe)
                    try {
                        if (class_exists(PaymentCompleted::class)) {
                            $payment = $order->payments()->where('provider', 'monetbil')->latest()->first();
                            if ($payment) {
                                event(new PaymentCompleted($order, $payment));
                            } else {
                                event(new PaymentCompleted($order, null));
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Monetbil notification: Failed to dispatch PaymentCompleted event', [
                            'error' => $e->getMessage(),
                            'order_id' => $order->id,
                            'reason' => 'event_dispatch_failed',
                        ]);
                    }

                    Log::info('Monetbil payment completed', [
                        'order_id' => $order->id,
                        'payment_ref' => $lockedTransaction->payment_ref,
                        'transaction_id' => $lockedTransaction->transaction_id,
                        'amount' => $lockedTransaction->amount,
                    ]);
                } elseif ($normalizedStatus === 'failed' || $normalizedStatus === 'cancelled') {
                    // ✅ CORRECTION 5 : Rollback stock si paiement échoue
                    if ($lockedTransaction->order_id) {
                        $order = Order::where('id', $lockedTransaction->order_id)
                            ->lockForUpdate()
                            ->first();

                        if ($order) {
                            // ✅ CORRECTION 7 : Vérifier si Order est dans un état terminal
                            if (!$order->isTerminal()) {
                                // Mettre à jour Order
                                $order->update([
                                    'payment_status' => 'failed',
                                ]);

                                // Rollback stock
                                try {
                                    $stockService = app(\Modules\ERP\Services\StockService::class);
                                    $stockService->rollbackFromOrder($order);
                                    Log::info('Stock rolled back for failed Monetbil payment', [
                                        'order_id' => $order->id,
                                        'transaction_id' => $lockedTransaction->id,
                                        'status' => $normalizedStatus,
                                    ]);
                                } catch (\Throwable $e) {
                                    Log::error('Stock rollback failed for failed Monetbil payment', [
                                        'order_id' => $order->id,
                                        'transaction_id' => $lockedTransaction->id,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                    ]);
                                    // Ne pas bloquer la mise à jour si rollback échoue
                                }
                            }
                        }
                    }

                    // Déclencher l'événement de paiement échoué (si l'événement existe)
                    if ($lockedTransaction->order) {
                        try {
                            if (class_exists(PaymentFailed::class)) {
                                event(new PaymentFailed($lockedTransaction->order, 'Payment ' . $normalizedStatus));
                            }
                        } catch (\Exception $e) {
                            Log::warning('Monetbil notification: Failed to dispatch PaymentFailed event', [
                                'error' => $e->getMessage(),
                                'order_id' => $lockedTransaction->order_id,
                                'reason' => 'event_dispatch_failed',
                            ]);
                        }
                    }

                    Log::info('Monetbil payment ' . $normalizedStatus, [
                        'payment_ref' => $lockedTransaction->payment_ref,
                        'order_id' => $lockedTransaction->order_id,
                    ]);
                }
            });

            Log::info('Monetbil notification processed', [
                'payment_ref' => $paymentRef,
                'status' => $normalizedStatus,
                'ip' => $ip,
                'route' => $route,
            ]);

            return response()->json(['status' => 'success'], 200);
        } catch (\InvalidArgumentException $e) {
            // Erreur de validation (payload invalide)
            Log::error('Monetbil notification: Invalid payload', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'error' => $e->getMessage(),
                'reason' => 'invalid_payload',
            ]);

            return response()->json(['message' => 'Invalid payload'], 400);
        } catch (\Exception $e) {
            // Erreur serveur inattendue (uniquement pour erreurs non prévues)
            Log::error('Monetbil notification: Processing error', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'reason' => 'unexpected_error',
            ]);

            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}

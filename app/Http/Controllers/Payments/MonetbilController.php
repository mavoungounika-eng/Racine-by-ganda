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
    protected \App\Services\SaaSCheckoutService $saasCheckoutService;
    protected \App\Services\Webhooks\WebhookDeduplicationService $deduplicationService;
    protected \App\Services\Webhooks\CircuitBreakerService $circuitBreaker;
    protected \App\Services\Webhooks\WebhookRetryService $retryService;

    public function __construct(
        MonetbilService $monetbilService, 
        \App\Services\SaaSCheckoutService $saasCheckoutService,
        \App\Services\Webhooks\WebhookDeduplicationService $deduplicationService,
        \App\Services\Webhooks\CircuitBreakerService $circuitBreaker,
        \App\Services\Webhooks\WebhookRetryService $retryService
    )
    {
        $this->monetbilService = $monetbilService;
        $this->saasCheckoutService = $saasCheckoutService;
        $this->deduplicationService = $deduplicationService;
        $this->circuitBreaker = $circuitBreaker;
        $this->retryService = $retryService;
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

            // ✅ MULTI-DEVISE : Détecter la devise par téléphone (XAF/XOF)
            $currencyService = app(\App\Services\Currency\CurrencyService::class);
            $detectedCurrency = $currencyService->detectCurrencyFromPhone($lockedOrder->customer_phone);

            // Créer ou mettre à jour la transaction en pending
            $transaction = PaymentTransaction::updateOrCreate(
                [
                    'payment_ref' => $paymentRef,
                    'order_id' => $lockedOrder->id,
                ],
                [
                    'provider' => 'monetbil',
                    'amount' => $lockedOrder->total_amount,
                    'currency' => $detectedCurrency,
                    'status' => 'pending',
                    'raw_payload' => [],
                ]
            );

            // ✅ SAAS PUR : Configurer les clés dynamiques (RACINE ou Créateur)
            $paymentConfig = $this->saasCheckoutService->getPaymentConfig($lockedOrder->creator_id);
            
            if (empty($paymentConfig['momo_api_key'])) {
                return redirect()->route('checkout.index')->with('error', 'La passerelle du vendeur n\'est pas configurée.');
            }

            // Injecter les clés dynamiques dans le service
            $this->monetbilService->setServiceKeys($paymentConfig['momo_provider'], $paymentConfig['momo_api_key']);

            // Construire le payload
            $payload = [
                'amount' => $lockedOrder->total_amount,
                'phone' => $lockedOrder->customer_phone,
                'currency' => $detectedCurrency,
                'payment_ref' => $paymentRef,
                'user_id' => $lockedOrder->user_id, // Metadata utile
                'email' => $lockedOrder->customer_email,
                'return_url' => route('checkout.success', ['order' => $lockedOrder->id]),
                'notify_url' => route('payment.monetbil.notify'), // Route générique centralisée
                'logo' => asset('img/logo.png'), 
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

        // ✅ CIRCUIT BREAKER: Check if Monetbil is failing
        if (!$this->circuitBreaker->isAvailable('monetbil')) {
            Log::warning('❌ Monetbil Circuit Breaker OPEN - rejecting webhook');
            return response()->json([
                'status' => 'circuit_open',
                'message' => 'Service temporarily unavailable'
            ], 503);
        }

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

            // 0. Validation des champs obligatoires
            if (empty($params['payment_ref'])) {
                return response()->json(['message' => 'Missing payment_ref'], 400);
            }
            if (empty($params['status'])) {
                return response()->json(['message' => 'Missing status'], 400);
            }
            // 2. Identification du créateur et vérification de la signature
            // Lookup order par payment_ref
            $transaction = PaymentTransaction::where('payment_ref', $params['payment_ref'] ?? '')->first();
            
            if (!$transaction) {
                Log::warning('Monetbil notification: Transaction not found', [
                    'payment_ref' => $params['payment_ref'] ?? 'unknown',
                ]);
                return response()->json(['message' => 'Transaction not found'], 404);
            }

            $order = Order::find($transaction->order_id);
            
            $isProduction = app()->environment('production') || config('app.env') === 'production';
            $hasSignature = isset($params['sign']);
            
            if ($isProduction && !$hasSignature) {
                return response()->json(['message' => 'Missing signature'], 401);
            }

            // ✅ SAAS PUR : Récupérer le secret dynamique du créateur pour vérifier la signature
            $dynamicSecret = null;
            if ($order) {
                 $paymentConfig = $this->saasCheckoutService->getPaymentConfig($order->creator_id);
                 $dynamicSecret = $paymentConfig['momo_api_key'] ?? null;
            }

            if (!$this->monetbilService->verifySignature($params, $dynamicSecret)) {
                if ($isProduction) {
                    Log::error('Monetbil notification: Invalid signature', [
                        'payment_ref' => $params['payment_ref'] ?? 'unknown',
                        'using_dynamic_secret' => !empty($dynamicSecret),
                    ]);
                    return response()->json(['message' => 'Invalid signature'], 401);
                }
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

            // 5c. DEDUPLICATION PERMANENTE : Check if already processed via centralized service
            if ($this->deduplicationService->isDuplicate('monetbil', (string)$paymentRef)) {
                Log::info('⏭️ Duplicate Monetbil webhook skipped', [
                    'payment_ref' => $paymentRef,
                ]);
                $this->circuitBreaker->recordSuccess('monetbil');
                return response()->json(['status' => 'success', 'message' => 'Already processed (infra)'], 200);
            }

            // 6. Normaliser le statut
            $normalizedStatus = $this->monetbilService->normalizeStatus($status);

            // 7. PREPARE PAYLOAD FOR RETRY
            $processingPayload = [
                'params' => $params,
                'paymentRef' => $paymentRef,
                'normalizedStatus' => $normalizedStatus,
                'transaction' => $transaction,
                'ip' => $ip,
                'route' => $route,
                'provider' => 'monetbil',
            ];

            // 8. EXECUTE WITH RETRY
            $success = $this->retryService->retry(
                handler: function (array $p) {
                    $params = $p['params'];
                    $normalizedStatus = $p['normalizedStatus'];
                    $transaction = $p['transaction'];
                    $ip = $p['ip'];
                    $route = $p['route'];

                    // PROTECTION RACE CONDITION : Transaction DB + lock
                    DB::transaction(function () use ($transaction, $normalizedStatus, $params, $ip, $route) {
                        // Verrouiller la transaction pour éviter race condition
                        $lockedTransaction = PaymentTransaction::where('id', $transaction->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$lockedTransaction) {
                            throw new \Exception('Transaction not found after lock: ' . $transaction->id);
                        }

                        // Vérifier à nouveau si déjà payé (double protection)
                        if ($lockedTransaction->isAlreadySuccessful()) {
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
                            $order = Order::where('id', $lockedTransaction->order_id)
                                ->lockForUpdate()
                                ->first();
                            
                            if (!$order) {
                                throw new \Exception('Order not found after lock: ' . $lockedTransaction->order_id);
                            }

                            if ($order->isTerminal()) {
                                return;
                            }

                            // Vérifier si un Payment existe déjà
                            $existingPayment = $order->payments()
                                ->where('provider', 'monetbil')
                                ->where('external_reference', $lockedTransaction->transaction_id ?? $lockedTransaction->payment_ref)
                                ->first();

                            if ($existingPayment) {
                                $order->update([
                                    'payment_status' => 'paid',
                                    'status' => 'processing',
                                ]);
                                return;
                            }
                            
                            $order->update([
                                'payment_status' => 'paid',
                                'status' => 'processing',
                            ]);

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

                            if (class_exists(PaymentCompleted::class)) {
                                $payment = $order->payments()->where('provider', 'monetbil')->latest()->first();
                                event(new PaymentCompleted($order, $payment));
                            }
                        } elseif ($normalizedStatus === 'failed' || $normalizedStatus === 'cancelled') {
                            if ($lockedTransaction->order_id) {
                                $order = Order::where('id', $lockedTransaction->order_id)
                                    ->lockForUpdate()
                                    ->first();

                                if ($order && !$order->isTerminal()) {
                                    $order->update(['payment_status' => 'failed']);
                                    $stockService = app(\Modules\ERP\Services\StockService::class);
                                    $stockService->rollbackFromOrder($order);
                                }
                            }

                            if ($lockedTransaction->order && class_exists(PaymentFailed::class)) {
                                event(new PaymentFailed($lockedTransaction->order, 'Payment ' . $normalizedStatus));
                            }
                        }
                    });
                },
                payload: $processingPayload,
                maxAttempts: 3,
                initialDelay: 1,
                webhookId: (string)$paymentRef
            );

            if ($success) {
                // 10. Mark as processed successfully in infra
                $this->deduplicationService->markAsProcessedSuccess('monetbil', (string)$paymentRef);
                $this->circuitBreaker->recordSuccess('monetbil');

                $failure = \App\Models\WebhookFailure::where('external_id', (string)$paymentRef)->first();
                if ($failure) {
                    $this->deduplicationService->markAsProcessed($failure);
                }

                return response()->json(['status' => 'success'], 200);
            }

            // If we reach here, retry failed
            $this->circuitBreaker->recordFailure('monetbil', 'Processing failed after retries');
            return response()->json(['status' => 'error', 'message' => 'Logged but failed'], 200);

        } catch (\InvalidArgumentException $e) {
            // Erreur de validation (payload invalide)
            Log::error('Monetbil notification: Invalid payload', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'error' => $e->getMessage(),
                'reason' => 'invalid_payload',
            ]);

            // Track failure for monitoring
            if (isset($paymentRef)) {
                $this->deduplicationService->recordFailure(
                    'monetbil',
                    $params['status'] ?? 'unknown',
                    (string)$paymentRef,
                    $params,
                    $params['sign'] ?? '',
                    $e->getMessage()
                );
            }

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

            // Track critical failure
            if (isset($paymentRef)) {
                $this->deduplicationService->recordFailure(
                    'monetbil',
                    $params['status'] ?? 'processing_error',
                    (string)$paymentRef,
                    $params,
                    $params['sign'] ?? '',
                    $e->getMessage()
                );
            }

            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}




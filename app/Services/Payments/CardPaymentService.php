<?php

namespace App\Services\Payments;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Service de paiement par carte bancaire via Stripe Checkout
 */
class CardPaymentService
{
    /**
     * Créer une session Stripe Checkout pour une commande
     *
     * @param Order $order La commande à payer
     * @return Payment L'enregistrement de paiement créé avec l'URL de la session
     * @throws PaymentException Si Stripe n'est pas configuré ou en cas d'erreur API
     */
    public function createCheckoutSession(Order $order): Payment
    {
        // ✅ CORRECTION 2 : Lock commande avant paiement pour éviter double paiement
        $lockedOrder = Order::where('id', $order->id)
            ->lockForUpdate()
            ->first();

        if (!$lockedOrder) {
            throw new PaymentException(
                'Commande introuvable',
                404,
                'La commande n\'existe plus.'
            );
        }

        // ✅ CORRECTION 4 : Vérifier si un paiement existe déjà pour cette commande
        $existingPayment = $lockedOrder->payments()
            ->whereIn('status', ['initiated', 'paid'])
            ->first();

        if ($existingPayment) {
            Log::info('Stripe: Payment already exists for order', [
                'order_id' => $lockedOrder->id,
                'payment_id' => $existingPayment->id,
                'payment_status' => $existingPayment->status,
            ]);
            return $existingPayment;
        }

        // ✅ CORRECTION 2 : Vérifier payment_status sous lock
        if ($lockedOrder->payment_status !== 'pending') {
            throw new PaymentException(
                'Commande déjà payée ou invalide',
                400,
                'Cette commande a déjà été payée ou n\'est plus valide.'
            );
        }

        // Vérifier que Stripe est activé
        $stripeConfig = config('services.stripe');
        if (empty($stripeConfig['secret'])) {
            throw new PaymentException(
                'Stripe non configuré',
                500,
                'Le paiement par carte bancaire est actuellement désactivé. Veuillez contacter le support.'
            );
        }

        // Configurer la clé API Stripe
        Stripe::setApiKey($stripeConfig['secret']);

        // Calculer le montant en centimes (Stripe utilise les plus petites unités)
        $amountInCents = intval($lockedOrder->total_amount * 100);

        // Créer un enregistrement Payment en base de données
        $payment = Payment::create([
            'order_id' => $lockedOrder->id,
            'amount' => $lockedOrder->total_amount,
            'currency' => config('services.stripe.currency', 'XAF'),
            'channel' => 'card',
            'provider' => 'stripe',
            'status' => 'initiated',
            'metadata' => [
                'order_id' => $lockedOrder->id,
                'customer_name' => $lockedOrder->customer_name,
                'customer_email' => $lockedOrder->customer_email,
            ],
        ]);

        try {
            // Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'success_url' => route('checkout.card.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.card.cancel', ['order' => $order->id]),
                'customer_email' => $lockedOrder->customer_email,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower(config('services.stripe.currency', 'xaf')),
                            'product_data' => [
                                'name' => 'Commande #' . $lockedOrder->id,
                                'description' => 'Paiement de la commande #' . $lockedOrder->id,
                            ],
                            'unit_amount' => $amountInCents,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'order_id' => $lockedOrder->id,
                    'payment_id' => $payment->id,
                ],
            ]);

            // Mettre à jour le Payment avec les informations de la session Stripe
            $payment->update([
                'external_reference' => $session->id,
                'provider_payment_id' => $session->payment_intent ?? null,
                'metadata' => array_merge($payment->metadata ?? [], [
                    'session_id' => $session->id,
                    'session_url' => $session->url,
                ]),
            ]);

            Log::info('Stripe Checkout session created', [
                'order_id' => $lockedOrder->id,
                'payment_id' => $payment->id,
                'session_id' => $session->id,
            ]);

            return $payment;
        } catch (ApiErrorException $e) {
            // En cas d'erreur Stripe, mettre à jour le statut du paiement
            $payment->update([
                'status' => 'failed',
                'metadata' => array_merge($payment->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);

            Log::error('Stripe Checkout session creation failed', [
                'order_id' => $lockedOrder->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            throw new PaymentException(
                'Erreur Stripe API: ' . $e->getMessage(),
                500,
                'Une erreur est survenue lors de la création de la session de paiement. Veuillez réessayer.'
            );
        }
    }

    /**
     * Traiter un webhook Stripe
     *
     * @param string $payload Raw payload content (JSON string)
     * @param string|null $signature Stripe signature from header
     * @return Payment|null
     * @throws SignatureVerificationException
     */
    /**
     * Traiter un webhook Stripe
     * 
     * RBG-P0-010 : Signature obligatoire en production
     * - Si signature absente → 401/403
     * - Si signature invalide → 401/403
     * - Logs structurés (ip, route, reason)
     *
     * @param string $payload Raw payload content (JSON string)
     * @param string|null $signature Stripe signature from header
     * @return Payment|null
     * @throws SignatureVerificationException Si signature invalide ou absente en production
     */
    public function handleWebhook(string $payload, ?string $signature = null): ?Payment
    {
        $webhookSecret = config('services.stripe.webhook_secret') ?? config('stripe.webhook_secret', '');
        // RBG-P0-010 : Détection d'environnement production (compatible tests)
        $isProduction = app()->environment('production') || config('app.env') === 'production';
        $ip = request()->ip();
        $route = request()->fullUrl();
        
        // RBG-P0-010 : Signature obligatoire en production
        if ($isProduction) {
            // Vérifier que la signature est présente
            if (empty($signature)) {
                Log::error('Stripe webhook: Missing signature in production', [
                    'ip' => $ip,
                    'route' => $route,
                    'reason' => 'missing_signature',
                    'user_agent' => request()->userAgent(),
                ]);
                throw new SignatureVerificationException(
                    'Missing Stripe-Signature header',
                    0
                );
            }
            
            // Vérifier que le secret est configuré
            if (empty($webhookSecret)) {
                Log::error('Stripe webhook: Webhook secret not configured in production', [
                    'ip' => $ip,
                    'route' => $route,
                    'reason' => 'missing_secret',
                ]);
                throw new \RuntimeException('Stripe webhook secret not configured');
            }
            
            // Vérifier la signature
            try {
                $event = Webhook::constructEvent(
                    $payload,
                    $signature,
                    $webhookSecret
                );
                
                Log::info('Stripe webhook signature verified', [
                    'ip' => $ip,
                    'route' => $route,
                    'event_id' => $event->id ?? null,
                    'event_type' => $event->type ?? null,
                ]);
            } catch (SignatureVerificationException $e) {
                Log::error('Stripe webhook: Invalid signature', [
                    'ip' => $ip,
                    'route' => $route,
                    'reason' => 'invalid_signature',
                    'error' => $e->getMessage(),
                    'user_agent' => request()->userAgent(),
                ]);
                throw $e;
            } catch (\Exception $e) {
                Log::error('Stripe webhook: Verification error', [
                    'ip' => $ip,
                    'route' => $route,
                    'reason' => 'verification_error',
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            // En développement : signature optionnelle mais recommandée
            if ($signature && $webhookSecret) {
                try {
                    $event = Webhook::constructEvent(
                        $payload,
                        $signature,
                        $webhookSecret
                    );
                    
                    Log::info('Stripe webhook signature verified (development)', [
                        'ip' => $ip,
                        'route' => $route,
                        'event_id' => $event->id ?? null,
                        'event_type' => $event->type ?? null,
                    ]);
                } catch (SignatureVerificationException $e) {
                    Log::warning('Stripe webhook: Invalid signature in development (continuing)', [
                        'ip' => $ip,
                        'route' => $route,
                        'error' => $e->getMessage(),
                    ]);
                    // En développement, on continue sans signature si invalide
                    $event = json_decode($payload, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::warning('Invalid JSON payload in webhook');
                        return null;
                    }
                }
            } else {
                Log::info('Stripe webhook processed without signature verification (development mode)', [
                    'ip' => $ip,
                    'route' => $route,
                    'has_signature' => !empty($signature),
                    'has_secret' => !empty($webhookSecret),
                ]);
                
                // Parser le payload manuellement
                $event = json_decode($payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('Invalid JSON payload in webhook');
                    return null;
                }
            }
        }

        // Extraire les données de l'événement
        $eventId = is_object($event) ? ($event->id ?? null) : ($event['id'] ?? null);
        $eventType = is_object($event) ? $event->type : ($event['type'] ?? null);
        $object = is_object($event) 
            ? ($event->data->object ?? null) 
            : ($event['data']['object'] ?? null);

        // Vérifier que event.id et event.type sont présents
        if (empty($eventId) || empty($eventType)) {
            Log::warning('Stripe webhook: Missing event.id or event.type', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);
            return null;
        }

        // Convertir l'objet en tableau si nécessaire
        if (is_object($object)) {
            $object = $object->toArray();
        }

        if (!$object) {
            Log::warning('Invalid webhook payload received', [
                'event_id' => $eventId,
                'event_type' => $eventType,
            ]);
            return null;
        }

        // IDEMPOTENCY : Insert-first pour éviter les race conditions
        try {
            $webhookEvent = StripeWebhookEvent::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'status' => 'received',
                'payload_hash' => hash('sha256', $payload),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate key = événement déjà traité (idempotence)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                $existingEvent = StripeWebhookEvent::where('event_id', $eventId)->first();
                
                if ($existingEvent && $existingEvent->isProcessed()) {
                    Log::info('Stripe webhook: Event already processed (idempotent)', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'status' => $existingEvent->status,
                    ]);
                    
                    // Retourner le Payment associé si disponible
                    if ($existingEvent->payment_id) {
                        return Payment::find($existingEvent->payment_id);
                    }
                    return null;
                }
            }
            
            // Autre erreur DB, relancer
            throw $e;
        }

        // Envelopper le traitement dans une transaction avec lock
        try {
            return DB::transaction(function () use ($webhookEvent, $eventId, $eventType, $object) {
                // Événements qui ne nécessitent pas de Payment associé
                $eventsWithoutPayment = ['payment_method.attached'];
                
                if (in_array($eventType, $eventsWithoutPayment)) {
                    // Traiter directement ces événements sans chercher de Payment
                    switch ($eventType) {
                        case 'payment_method.attached':
                            $this->handlePaymentMethodAttached($object);
                            $webhookEvent->markAsProcessed();
                            return null;
                            
                        default:
                            Log::info('Unhandled webhook event type (without payment)', [
                                'event_id' => $eventId,
                                'event_type' => $eventType,
                            ]);
                            $webhookEvent->markAsIgnored();
                            return null;
                    }
                }

                // Pour les autres événements, chercher le Payment associé
                $sessionId = $object['id'] ?? null;
                $paymentIntentId = $object['payment_intent'] ?? null;
                $payment = null;

                // Essayer de trouver par session_id d'abord
                if ($sessionId) {
                    $payment = Payment::where('external_reference', $sessionId)
                        ->where('channel', 'card')
                        ->where('provider', 'stripe')
                        ->lockForUpdate()
                        ->first();
                }

                // Si pas trouvé par session_id, essayer par payment_intent
                if (!$payment && $paymentIntentId) {
                    $payment = Payment::where('provider_payment_id', $paymentIntentId)
                        ->where('channel', 'card')
                        ->where('provider', 'stripe')
                        ->lockForUpdate()
                        ->first();
                }

                if (!$payment) {
                    Log::warning('Payment not found for webhook', [
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'session_id' => $sessionId,
                        'payment_intent_id' => $paymentIntentId,
                    ]);
                    
                    // Marquer l'événement comme ignoré (pas de Payment associé)
                    $webhookEvent->markAsIgnored();
                    return null;
                }

                // Recharger le Payment verrouillé pour avoir les dernières données
                $payment->refresh();

                // Vérifier si déjà payé (après lock pour éviter race condition)
                if ($payment->status === 'paid') {
                    Log::info('Stripe webhook: Payment already paid (idempotent)', [
                        'event_id' => $eventId,
                        'payment_id' => $payment->id,
                    ]);
                    
                    // Marquer l'événement comme ignoré (déjà traité)
                    $webhookEvent->update([
                        'payment_id' => $payment->id,
                        'status' => 'ignored',
                        'processed_at' => now(),
                    ]);
                    
                    return $payment;
                }

                // Traiter les différents types d'événements
                switch ($eventType) {
                    case 'checkout.session.completed':
                        $this->handleCheckoutSessionCompleted($payment, $object);
                        break;

                    case 'payment_intent.succeeded':
                        $this->handlePaymentIntentSucceeded($payment, $object);
                        break;

                    case 'payment_intent.payment_failed':
                        $this->handlePaymentIntentFailed($payment, $object);
                        break;

                    default:
                        Log::info('Unhandled webhook event type', [
                            'event_id' => $eventId,
                            'event_type' => $eventType,
                        ]);
                        $webhookEvent->markAsIgnored();
                        return $payment;
                }

                // Marquer l'événement comme traité
                $webhookEvent->markAsProcessed($payment->id);

                return $payment;
            });
        } catch (\Throwable $e) {
            // En cas d'erreur, marquer l'événement comme échoué
            try {
                $webhookEvent->markAsFailed();
            } catch (\Exception $updateException) {
                Log::error('Failed to mark webhook event as failed', [
                    'event_id' => $eventId,
                    'error' => $updateException->getMessage(),
                ]);
            }

            // Relancer l'exception pour que le controller renvoie 500
            throw $e;
        }
    }

    /**
     * Gérer l'événement checkout.session.completed
     *
     * @param Payment $payment
     * @param array $session
     * @return void
     */
    protected function handleCheckoutSessionCompleted(Payment $payment, array $session): void
    {
        if ($payment->status === 'paid') {
            return; // Déjà traité
        }

        // ✅ CORRECTION 3 : Transaction atomique Payment + Order
        // Règle absolue : Payment = paid ⇔ Order.payment_status = paid
        DB::transaction(function () use ($payment, $session) {
            // Lock Payment et Order pour atomicité
            $lockedPayment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment || $lockedPayment->status === 'paid') {
                return; // Déjà traité
            }

            $order = $lockedPayment->order;
            if (!$order) {
                Log::warning('Stripe webhook: Order not found for payment', [
                    'payment_id' => $lockedPayment->id,
                ]);
                return;
            }

            // ✅ CORRECTION 7 : Vérifier si Order est dans un état terminal
            if ($order->isTerminal()) {
                Log::info('Stripe webhook: Order already in terminal state', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ]);
                return;
            }

            // Lock Order
            $lockedOrder = Order::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                Log::warning('Stripe webhook: Order not found after lock', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // Mettre à jour Payment et Order atomiquement
            $lockedPayment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_payment_id' => $session['payment_intent'] ?? $lockedPayment->provider_payment_id,
                'metadata' => array_merge($lockedPayment->metadata ?? [], [
                    'checkout_completed_at' => now()->toIso8601String(),
                    'payment_status' => $session['payment_status'] ?? null,
                ]),
            ]);

            $lockedOrder->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Statut commande = processing (pas 'paid')
            ]);

            Log::info('Order payment completed', [
                'order_id' => $lockedOrder->id,
                'payment_id' => $lockedPayment->id,
            ]);

            // Phase 3 : Émettre l'event PaymentCompleted pour le monitoring
            event(new PaymentCompleted($lockedOrder, $lockedPayment));
        });
    }

    /**
     * Gérer l'événement payment_intent.succeeded
     *
     * @param Payment $payment
     * @param array $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentSucceeded(Payment $payment, array $paymentIntent): void
    {
        if ($payment->status === 'paid') {
            return; // Déjà traité
        }

        // ✅ CORRECTION 3 : Transaction atomique Payment + Order
        // Règle absolue : Payment = paid ⇔ Order.payment_status = paid
        DB::transaction(function () use ($payment, $paymentIntent) {
            // Lock Payment et Order pour atomicité
            $lockedPayment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment || $lockedPayment->status === 'paid') {
                return; // Déjà traité
            }

            $order = $lockedPayment->order;
            if (!$order) {
                Log::warning('Stripe webhook: Order not found for payment', [
                    'payment_id' => $lockedPayment->id,
                ]);
                return;
            }

            // ✅ CORRECTION 7 : Vérifier si Order est dans un état terminal
            if ($order->isTerminal()) {
                Log::info('Stripe webhook: Order already in terminal state', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                ]);
                return;
            }

            // Lock Order
            $lockedOrder = Order::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                Log::warning('Stripe webhook: Order not found after lock', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // Mettre à jour Payment et Order atomiquement
            $lockedPayment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge($lockedPayment->metadata ?? [], [
                    'payment_intent_succeeded_at' => now()->toIso8601String(),
                ]),
            ]);

            $lockedOrder->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Statut commande = processing (pas 'paid')
            ]);
        });
    }

    /**
     * Gérer l'événement payment_intent.payment_failed
     *
     * @param Payment $payment
     * @param array $paymentIntent
     * @return void
     */
    protected function handlePaymentIntentFailed(Payment $payment, array $paymentIntent): void
    {
        // ✅ CORRECTION 3 : Transaction atomique Payment + Order
        DB::transaction(function () use ($payment, $paymentIntent) {
            // Lock Payment
            $lockedPayment = Payment::where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPayment) {
                return;
            }

            // ✅ CORRECTION 7 : Vérifier si Payment est dans un état terminal
            if ($lockedPayment->isTerminal()) {
                Log::info('Stripe webhook: Payment already in terminal state', [
                    'payment_id' => $lockedPayment->id,
                    'status' => $lockedPayment->status,
                ]);
                return;
            }

            $order = $lockedPayment->order;
            if (!$order) {
                Log::warning('Stripe webhook: Order not found for payment', [
                    'payment_id' => $lockedPayment->id,
                ]);
                return;
            }

            // Lock Order
            $lockedOrder = Order::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedOrder) {
                Log::warning('Stripe webhook: Order not found after lock', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            // Mettre à jour Payment
            $lockedPayment->update([
                'status' => 'failed',
                'metadata' => array_merge($lockedPayment->metadata ?? [], [
                    'payment_failed_at' => now()->toIso8601String(),
                    'failure_message' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                ]),
            ]);

            // Mettre à jour Order
            $lockedOrder->update([
                'payment_status' => 'failed',
            ]);

            // ✅ CORRECTION 5 : Rollback stock si paiement échoue
            try {
                $stockService = app(\Modules\ERP\Services\StockService::class);
                $stockService->rollbackFromOrder($lockedOrder);
                Log::info('Stock rolled back for failed payment', [
                    'order_id' => $lockedOrder->id,
                    'payment_id' => $lockedPayment->id,
                ]);
            } catch (\Throwable $e) {
                Log::error('Stock rollback failed for failed payment', [
                    'order_id' => $lockedOrder->id,
                    'payment_id' => $lockedPayment->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // Ne pas bloquer la mise à jour du paiement si rollback échoue
            }

            Log::warning('Payment intent failed', [
                'payment_id' => $lockedPayment->id,
                'order_id' => $lockedOrder->id,
            ]);

            // Déclencher l'événement de paiement échoué
            event(new PaymentFailed($lockedOrder, 'Payment failed'));
        });
    }

    /**
     * Gérer l'événement payment_method.attached
     * 
     * Cet événement se produit lorsqu'une méthode de paiement est attachée à un client Stripe.
     * Il n'est pas directement lié à un paiement spécifique, mais peut être utile pour le suivi.
     *
     * @param array $paymentMethod
     * @return void
     */
    protected function handlePaymentMethodAttached(array $paymentMethod): void
    {
        $paymentMethodId = $paymentMethod['id'] ?? null;
        $customerId = $paymentMethod['customer'] ?? null;
        $type = $paymentMethod['type'] ?? null;

        Log::info('Payment method attached', [
            'payment_method_id' => $paymentMethodId,
            'customer_id' => $customerId,
            'type' => $type,
        ]);

        // Vous pouvez ajouter ici une logique supplémentaire si nécessaire,
        // par exemple sauvegarder les méthodes de paiement des clients pour réutilisation
    }
}

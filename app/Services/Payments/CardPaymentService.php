<?php

namespace App\Services\Payments;

use App\Events\PaymentCompleted;
use App\Events\PaymentFailed;
use App\Exceptions\PaymentException;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Config;
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
        $amountInCents = intval($order->total_amount * 100);

        // Créer un enregistrement Payment en base de données
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'currency' => config('services.stripe.currency', 'XAF'),
            'channel' => 'card',
            'provider' => 'stripe',
            'status' => 'initiated',
            'metadata' => [
                'order_id' => $order->id,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
            ],
        ]);

        try {
            // Créer la session Stripe Checkout
            $session = Session::create([
                'payment_method_types' => ['card'],
                'mode' => 'payment',
                'success_url' => route('checkout.card.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.card.cancel', ['order' => $order->id]),
                'customer_email' => $order->customer_email,
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower(config('services.stripe.currency', 'xaf')),
                            'product_data' => [
                                'name' => 'Commande #' . $order->id,
                                'description' => 'Paiement de la commande #' . $order->id,
                            ],
                            'unit_amount' => $amountInCents,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'metadata' => [
                    'order_id' => $order->id,
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
                'order_id' => $order->id,
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
                'order_id' => $order->id,
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
    public function handleWebhook(string $payload, ?string $signature = null): ?Payment
    {
        $webhookSecret = config('services.stripe.webhook_secret') ?? config('stripe.webhook_secret');
        
        // Vérifier la signature du webhook en production
        if ($signature && $webhookSecret) {
            try {
                $event = Webhook::constructEvent(
                    $payload,
                    $signature,
                    $webhookSecret
                );
                
                Log::info('Stripe webhook signature verified', [
                    'event_id' => $event->id ?? null,
                    'event_type' => $event->type ?? null,
                ]);
            } catch (SignatureVerificationException $e) {
                Log::error('Stripe webhook signature verification failed', [
                    'error' => $e->getMessage(),
                    'ip' => request()->ip(),
                ]);
                throw $e;
            } catch (\Exception $e) {
                Log::error('Stripe webhook verification error', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        } else {
            // En mode développement ou si pas de secret configuré
            if (app()->environment('production')) {
                Log::warning('Stripe webhook secret not configured in production', [
                    'has_signature' => !empty($signature),
                    'has_secret' => !empty($webhookSecret),
                ]);
            } else {
                Log::info('Stripe webhook processed without signature verification (development mode)');
            }
            
            // Parser le payload manuellement
            $event = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Invalid JSON payload in webhook');
                return null;
            }
        }

        // Extraire les données de l'événement
        $eventType = is_object($event) ? $event->type : ($event['type'] ?? null);
        $object = is_object($event) 
            ? ($event->data->object ?? null) 
            : ($event['data']['object'] ?? null);

        if (!$eventType || !$object) {
            Log::warning('Invalid webhook payload received', [
                'event_type' => $eventType,
                'has_object' => !empty($object),
            ]);
            return null;
        }

        // Convertir l'objet en tableau si nécessaire
        if (is_object($object)) {
            $object = $object->toArray();
        }

        Log::info('Stripe webhook received', ['event_type' => $eventType]);

        // Chercher le Payment par external_reference (session_id) ou payment_intent
        $sessionId = $object['id'] ?? null;
        $paymentIntentId = $object['payment_intent'] ?? null;
        $payment = null;

        // Essayer de trouver par session_id d'abord
        if ($sessionId) {
            $payment = Payment::where('external_reference', $sessionId)
                ->where('channel', 'card')
                ->where('provider', 'stripe')
                ->first();
        }

        // Si pas trouvé par session_id, essayer par payment_intent
        if (!$payment && $paymentIntentId) {
            $payment = Payment::where('provider_payment_id', $paymentIntentId)
                ->where('channel', 'card')
                ->where('provider', 'stripe')
                ->first();
        }

        if (!$payment) {
            Log::warning('Payment not found for webhook', [
                'event_type' => $eventType,
                'session_id' => $sessionId,
            ]);
            return null;
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
                Log::info('Unhandled webhook event type', ['event_type' => $eventType]);
        }

        return $payment;
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

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'provider_payment_id' => $session['payment_intent'] ?? $payment->provider_payment_id,
            'metadata' => array_merge($payment->metadata ?? [], [
                'checkout_completed_at' => now()->toIso8601String(),
                'payment_status' => $session['payment_status'] ?? null,
            ]),
        ]);

        // Mettre à jour la commande
        $order = $payment->order;
        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Statut commande = processing (pas 'paid')
            ]);

            Log::info('Order payment completed', [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
            ]);

            // Phase 3 : Émettre l'event PaymentCompleted pour le monitoring
            event(new PaymentCompleted($order, $payment));
        }
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

        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], [
                'payment_intent_succeeded_at' => now()->toIso8601String(),
            ]),
        ]);

        // Mettre à jour la commande
        $order = $payment->order;
        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'processing', // Statut commande = processing (pas 'paid')
            ]);
        }
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
        $payment->update([
            'status' => 'failed',
            'metadata' => array_merge($payment->metadata ?? [], [
                'payment_failed_at' => now()->toIso8601String(),
                'failure_message' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
            ]),
        ]);

        Log::warning('Payment intent failed', [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
        ]);
    }
}

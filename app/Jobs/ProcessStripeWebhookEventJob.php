<?php

namespace App\Jobs;

use App\Models\StripeWebhookEvent;
use App\Models\Payment;
use App\Services\Payments\PaymentEventMapperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job pour traiter un événement Stripe webhook
 * 
 * Pattern v1.1 : "process only" - l'événement est déjà persisté par le controller
 * Idempotent : safe re-run, utilise locks DB
 * ShouldBeUnique : Protection supplémentaire contre race conditions
 */
class ProcessStripeWebhookEventJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives
     */
    public $tries = 3;

    /**
     * Timeout en secondes
     */
    public $timeout = 60;

    /**
     * Backoff entre les tentatives (secondes)
     */
    public $backoff = [10, 30, 60];

    /**
     * ID de l'événement Stripe à traiter
     */
    public function __construct(
        public int $stripeWebhookEventId
    ) {}

    /**
     * Identifiant unique pour ShouldBeUnique (évite les doublons)
     */
    public function uniqueId(): string
    {
        return 'stripe_webhook_event_' . $this->stripeWebhookEventId;
    }

    /**
     * Durée de l'unicité (secondes) - 5 minutes
     */
    public int $uniqueFor = 300;

    /**
     * Exécuter le job
     */
    public function handle(PaymentEventMapperService $mapperService): void
    {
        // Récupérer l'événement avec lock dans une transaction DB pour éviter les race conditions
        $event = DB::transaction(function () {
            return StripeWebhookEvent::lockForUpdate()
                ->find($this->stripeWebhookEventId);
        });

        if (!$event) {
            Log::warning('ProcessStripeWebhookEventJob: Event not found', [
                'event_id' => $this->stripeWebhookEventId,
            ]);
            return;
        }

        // IDEMPOTENCE : Si déjà traité ou ignoré, ne pas retraiter
        if ($event->isProcessed()) {
            Log::info('ProcessStripeWebhookEventJob: Event already processed (idempotence)', [
                'event_id' => $event->event_id,
                'status' => $event->status,
            ]);
            return;
        }

        try {
            // Mapper l'événement vers un statut
            $status = $mapperService->mapStripeEventToStatus($event->event_type);

            if ($status === null) {
                // Événement ignoré (non pertinent pour les paiements)
                $event->markAsIgnored();
                Log::info('ProcessStripeWebhookEventJob: Event ignored', [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                ]);
                return;
            }

            // Trouver le Payment associé
            $payment = $this->findPayment($event);

            if (!$payment) {
                // Si Payment introuvable, retry si on n'est pas à la dernière tentative
                if ($this->attempts() < $this->tries) {
                    Log::warning('ProcessStripeWebhookEventJob: Payment not found, retrying', [
                        'event_id' => $event->event_id,
                        'event_type' => $event->event_type,
                        'attempt' => $this->attempts(),
                        'max_tries' => $this->tries,
                        'payment_intent_id' => $event->payment_intent_id,
                        'checkout_session_id' => $event->checkout_session_id,
                    ]);
                    throw new \Exception('Payment not found for Stripe webhook event, retrying...');
                }

                // Dernière tentative : marquer l'événement comme failed avec message explicite
                $errorMessage = sprintf(
                    'Payment not found for Stripe webhook event. payment_intent_id=%s, checkout_session_id=%s',
                    $event->payment_intent_id ?? 'null',
                    $event->checkout_session_id ?? 'null'
                );
                $event->markAsFailed();
                Log::error('ProcessStripeWebhookEventJob: Payment not found after all retries', [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'payment_intent_id' => $event->payment_intent_id,
                    'checkout_session_id' => $event->checkout_session_id,
                    'error_message' => $errorMessage,
                ]);
                return;
            }

            // IDEMPOTENCE : Si Payment déjà dans le même statut final, ignorer
            if (($payment->status === 'paid' && $status === 'succeeded') ||
                ($payment->status === 'failed' && $status === 'failed') ||
                ($payment->status === 'refunded' && $status === 'refunded')) {
                Log::info('ProcessStripeWebhookEventJob: Payment already in same status (idempotence)', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'new_status' => $status,
                    'event_id' => $event->event_id,
                ]);
                $event->markAsProcessed($payment->id);
                return;
            }

            // Mettre à jour le Payment et la commande
            if (!method_exists($mapperService, 'updatePaymentAndOrder')) {
                // Si updatePaymentAndOrder n'existe pas, retry si on n'est pas à la dernière tentative
                if ($this->attempts() < $this->tries) {
                    Log::warning('ProcessStripeWebhookEventJob: updatePaymentAndOrder not available, retrying', [
                        'event_id' => $event->event_id,
                        'attempt' => $this->attempts(),
                        'max_tries' => $this->tries,
                    ]);
                    throw new \Exception('updatePaymentAndOrder method not implemented in PaymentEventMapperService, retrying...');
                }

                // Dernière tentative : marquer l'événement comme failed avec message explicite
                $errorMessage = 'updatePaymentAndOrder method not implemented in PaymentEventMapperService';
                $event->markAsFailed();
                Log::error('ProcessStripeWebhookEventJob: updatePaymentAndOrder not available after all retries', [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'payment_id' => $payment->id,
                    'error_message' => $errorMessage,
                ]);
                return;
            }

            $mapperService->updatePaymentAndOrder($payment, $status);

            // Marquer l'événement comme traité avec le payment_id
            $event->markAsProcessed($payment->id);

            Log::info('ProcessStripeWebhookEventJob: Event processed successfully', [
                'event_id' => $event->event_id,
                'payment_id' => $payment->id,
                'status' => $status,
            ]);

        } catch (\Throwable $e) {
            // Marquer l'événement comme échoué seulement si c'est une vraie erreur système
            // après tentative de traitement (pas un safe no-op)
            $event->markAsFailed();

            // Logger strict : aucun secret, payload, headers, signature
            // Limiter le message d'erreur à 200 caractères pour éviter fuite de données
            $errorMessage = mb_substr($e->getMessage(), 0, 200);
            
            Log::error('ProcessStripeWebhookEventJob: Processing failed', [
                'event_id' => $event->event_id,
                'event_type' => $event->event_type, // event_type non sensible (ex: payment_intent.succeeded)
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'error' => $errorMessage,
            ]);

            throw $e; // Relancer pour que le job soit marqué comme failed
        }
    }

    /**
     * Trouver le Payment associé à l'événement Stripe
     * 
     * Stratégie de mapping déterministe :
     * - Priorité 1 : Payment.provider_payment_id == StripeWebhookEvent.payment_intent_id
     * - Priorité 2 : Payment.external_reference == StripeWebhookEvent.checkout_session_id
     *
     * @param StripeWebhookEvent $event
     * @return Payment|null
     */
    private function findPayment(StripeWebhookEvent $event): ?Payment
    {
        // Priorité 1 : Chercher par payment_intent_id (provider_payment_id)
        if (!empty($event->payment_intent_id)) {
            $payment = Payment::where('provider_payment_id', $event->payment_intent_id)
                ->where('provider', 'stripe')
                ->where('channel', 'card')
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        // Priorité 2 : Chercher par checkout_session_id (external_reference)
        if (!empty($event->checkout_session_id)) {
            $payment = Payment::where('external_reference', $event->checkout_session_id)
                ->where('provider', 'stripe')
                ->where('channel', 'card')
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        return null;
    }
}



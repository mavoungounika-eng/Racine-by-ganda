<?php

namespace App\Jobs;

use App\Models\MonetbilCallbackEvent;
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
use Illuminate\Support\Facades\Schema;

/**
 * Job pour traiter un événement Monetbil callback
 * 
 * Pattern v1.1 : "process only" - l'événement est déjà persisté par le controller
 * Idempotent : safe re-run, utilise locks DB
 * ShouldBeUnique : Protection supplémentaire contre race conditions
 */
class ProcessMonetbilCallbackEventJob implements ShouldQueue, ShouldBeUnique
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
     * ID de l'événement Monetbil à traiter
     */
    public function __construct(
        public int $monetbilCallbackEventId
    ) {}

    /**
     * Identifiant unique pour ShouldBeUnique (évite les doublons)
     */
    public function uniqueId(): string
    {
        return 'monetbil_callback_event_' . $this->monetbilCallbackEventId;
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
        // Récupérer l'événement avec lock pour éviter les race conditions
        $event = DB::transaction(function () {
            return MonetbilCallbackEvent::lockForUpdate()
                ->find($this->monetbilCallbackEventId);
        });

        if (!$event) {
            Log::warning('ProcessMonetbilCallbackEventJob: Event not found', [
                'event_id' => $this->monetbilCallbackEventId,
            ]);
            return;
        }

        // IDEMPOTENCE : Si déjà traité, ne pas retraiter
        if (in_array($event->status, ['processed', 'ignored'])) {
            Log::info('ProcessMonetbilCallbackEventJob: Event already processed (idempotence)', [
                'event_key' => $event->event_key,
                'status' => $event->status,
            ]);
            return;
        }

        try {
            // Mapper l'événement vers un statut
            $status = $mapperService->mapMonetbilEventToStatus($event->payload ?? []);

            if ($status === null) {
                // Événement ignoré (non pertinent pour les paiements)
                $event->update([
                    'status' => 'ignored',
                    'processed_at' => now(),
                ]);
                Log::info('ProcessMonetbilCallbackEventJob: Event ignored', [
                    'event_key' => $event->event_key,
                ]);
                return;
            }

            // Trouver le Payment associé
            $payment = $this->findPayment($event);

            if (!$payment) {
                // Si Payment introuvable, retry si on n'est pas à la dernière tentative
                if ($this->attempts() < $this->tries) {
                    Log::warning('ProcessMonetbilCallbackEventJob: Payment not found, retrying', [
                        'event_key' => $event->event_key,
                        'transaction_id' => $event->transaction_id,
                        'attempt' => $this->attempts(),
                        'max_tries' => $this->tries,
                    ]);
                    throw new \Exception('Payment not found for Monetbil callback event, retrying...');
                }

                // Dernière tentative : marquer l'événement comme failed avec message explicite
                $errorMessage = sprintf(
                    'Payment not found for Monetbil callback event. transaction_id=%s',
                    $event->transaction_id ?? 'null'
                );
                $updateData = ['status' => 'failed'];
                // Conditionner l'update de 'error' uniquement si la colonne existe
                if (Schema::hasColumn('monetbil_callback_events', 'error')) {
                    $updateData['error'] = mb_substr($errorMessage, 0, 200);
                }
                $event->update($updateData);
                Log::error('ProcessMonetbilCallbackEventJob: Payment not found after all retries', [
                    'event_key' => $event->event_key,
                    'transaction_id' => $event->transaction_id,
                    'error_message' => $errorMessage,
                ]);
                return;
            }

            // IDEMPOTENCE : Si Payment déjà dans le même statut final, ignorer
            if (($payment->status === 'paid' && $status === 'succeeded') ||
                ($payment->status === 'failed' && $status === 'failed') ||
                ($payment->status === 'refunded' && $status === 'refunded')) {
                Log::info('ProcessMonetbilCallbackEventJob: Payment already in same status (idempotence)', [
                    'payment_id' => $payment->id,
                    'payment_status' => $payment->status,
                    'new_status' => $status,
                    'event_key' => $event->event_key,
                ]);
                $event->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
                return;
            }

            // Mettre à jour le Payment et la commande
            if (!method_exists($mapperService, 'updatePaymentAndOrder')) {
                // Si updatePaymentAndOrder n'existe pas, retry si on n'est pas à la dernière tentative
                if ($this->attempts() < $this->tries) {
                    Log::warning('ProcessMonetbilCallbackEventJob: updatePaymentAndOrder not available, retrying', [
                        'event_key' => $event->event_key,
                        'attempt' => $this->attempts(),
                        'max_tries' => $this->tries,
                    ]);
                    throw new \Exception('updatePaymentAndOrder method not implemented in PaymentEventMapperService, retrying...');
                }

                // Dernière tentative : marquer l'événement comme failed avec message explicite
                $errorMessage = 'updatePaymentAndOrder method not implemented in PaymentEventMapperService';
                $updateData = ['status' => 'failed'];
                // Conditionner l'update de 'error' uniquement si la colonne existe
                if (Schema::hasColumn('monetbil_callback_events', 'error')) {
                    $updateData['error'] = mb_substr($errorMessage, 0, 200);
                }
                $event->update($updateData);
                Log::error('ProcessMonetbilCallbackEventJob: updatePaymentAndOrder not available after all retries', [
                    'event_key' => $event->event_key,
                    'payment_id' => $payment->id,
                    'error_message' => $errorMessage,
                ]);
                return;
            }

            $mapperService->updatePaymentAndOrder($payment, $status);

            // Marquer l'événement comme traité
            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            Log::info('ProcessMonetbilCallbackEventJob: Event processed successfully', [
                'event_key' => $event->event_key,
                'payment_id' => $payment->id,
                'status' => $status,
            ]);

        } catch (\Throwable $e) {
            // Marquer l'événement comme échoué
            // Logger strict : aucun secret, payload, headers, signature
            // Limiter le message d'erreur à 200 caractères pour éviter fuite de données
            $errorMessage = mb_substr($e->getMessage(), 0, 200);
            
            $updateData = ['status' => 'failed'];
            // Conditionner l'update de 'error' uniquement si la colonne existe
            if (Schema::hasColumn('monetbil_callback_events', 'error')) {
                $updateData['error'] = $errorMessage;
            }
            $event->update($updateData);
            
            Log::error('ProcessMonetbilCallbackEventJob: Processing failed', [
                'event_key' => $event->event_key,
                'exception_class' => get_class($e),
                'exception_code' => $e->getCode(),
                'error_message' => $errorMessage,
            ]);

            throw $e; // Relancer pour que le job soit marqué comme failed
        }
    }

    /**
     * Trouver le Payment associé à l'événement Monetbil
     * 
     * Stratégie de mapping déterministe :
     * - Payment.external_reference == MonetbilCallbackEvent.transaction_id
     * - channel='mobile_money' (provider peut varier : mtn_momo, airtel_money, etc.)
     *
     * @param MonetbilCallbackEvent $event
     * @return Payment|null
     */
    private function findPayment(MonetbilCallbackEvent $event): ?Payment
    {
        // Chercher par transaction_id (external_reference dans Payment)
        if (!empty($event->transaction_id)) {
            $payment = Payment::where('external_reference', $event->transaction_id)
                ->where('channel', 'mobile_money')
                ->whereNotNull('order_id')
                ->first();

            if ($payment) {
                return $payment;
            }
        }

        return null;
    }
}



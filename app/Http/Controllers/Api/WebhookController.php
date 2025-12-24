<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMonetbilCallbackEventJob;
use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Contrôleur pour les webhooks/callbacks (pattern v1.1 : persist event d'abord, puis dispatch job)
 * 
 * Pattern : verify → persist event (idempotent) → dispatch job → return 200 vite
 */
class WebhookController extends Controller
{
    /**
     * Webhook Stripe
     * 
     * Pattern v1.1 : verify → persist event → dispatch job → 200 rapide
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stripe(Request $request): JsonResponse
    {
        // Récupérer le payload brut (important pour vérification signature Stripe)
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret') ?? '';
        $isProduction = app()->environment('production');

        // Log safe (début de méthode) - Ne jamais logger payload brut ni headers sensibles
        $signatureHeaderPresent = !empty($signature);
        Log::info('received_stripe_webhook', [
            'received_stripe_webhook' => true,
            'signature_header_present' => $signatureHeaderPresent,
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 100), // Limiter la taille
        ]);

        // 1. VERIFY signature (OBLIGATOIRE en production)
        try {
            // En production : signature OBLIGATOIRE
            if ($isProduction) {
                if (empty($signature)) {
                    Log::error('Stripe webhook: Missing signature in production', [
                        'ip' => $request->ip(),
                        'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                        'reason' => 'missing_signature',
                    ]);
                    return response()->json(['error' => 'Missing signature'], 401);
                }

                if (empty($webhookSecret)) {
                    Log::error('Stripe webhook: Webhook secret not configured', [
                        'ip' => $request->ip(),
                        'reason' => 'missing_secret',
                    ]);
                    return response()->json(['error' => 'Configuration error'], 500);
                }

                // Vérifier la signature avec Stripe\Webhook::constructEvent
                try {
                    $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
                    Log::info('Stripe webhook: Signature verified', [
                        'ip' => $request->ip(),
                    ]);
                } catch (SignatureVerificationException $e) {
                    // En production : REFUSER systématiquement si signature invalide
                    Log::error('Stripe webhook: Invalid signature in production', [
                        'ip' => $request->ip(),
                        'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                        'error' => mb_substr($e->getMessage(), 0, 200), // Limiter taille pour sécurité
                        'reason' => 'invalid_signature',
                    ]);
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            } else {
                // En développement : signature optionnelle mais recommandée
                if ($signature && $webhookSecret) {
                    try {
                        $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
                        Log::info('Stripe webhook: Signature verified (development)', [
                            'ip' => $request->ip(),
                        ]);
                    } catch (SignatureVerificationException $e) {
                        Log::warning('Stripe webhook: Invalid signature in development (continuing)', [
                            'ip' => $request->ip(),
                            'error' => mb_substr($e->getMessage(), 0, 200),
                        ]);
                        // En développement, continuer sans signature si invalide
                        $event = json_decode($payload, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::error('Stripe webhook: Invalid JSON', [
                                'ip' => $request->ip(),
                            ]);
                            return response()->json(['error' => 'Invalid JSON'], 400);
                        }
                    }
                } else {
                    // Dev mode : parser sans vérification
                    Log::info('Stripe webhook: Processing without signature verification (development)', [
                        'ip' => $request->ip(),
                        'has_signature' => !empty($signature),
                        'has_secret' => !empty($webhookSecret),
                    ]);
                    $event = json_decode($payload, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('Stripe webhook: Invalid JSON', [
                            'ip' => $request->ip(),
                        ]);
                        return response()->json(['error' => 'Invalid JSON'], 400);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Stripe webhook: Verification error', [
                'ip' => $request->ip(),
                'error' => mb_substr($e->getMessage(), 0, 200),
                'exception_class' => get_class($e),
            ]);
            return response()->json(['error' => 'Verification failed'], 400);
        }

        // Extraire event_id et event_type
        $eventId = is_object($event) ? ($event->id ?? null) : ($event['id'] ?? null);
        $eventType = is_object($event) ? ($event->type ?? null) : ($event['type'] ?? null);

        if (empty($eventId) || empty($eventType)) {
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Log safe avec event_id et event_type (après extraction)
        Log::info('received_stripe_webhook', [
            'received_stripe_webhook' => true,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'signature_header_present' => $signatureHeaderPresent,
            'ip' => $request->ip(),
        ]);

        // Normaliser l'événement en array pour extraction des identifiants
        $eventArray = is_object($event) ? json_decode(json_encode($event), true) : $event;

        // Extraire les identifiants Stripe (checkout_session_id, payment_intent_id)
        $identifiers = $this->extractStripeIdentifiers($eventArray);

        // 2. PERSIST EVENT (idempotent)
        try {
            $webhookEvent = StripeWebhookEvent::firstOrCreate(
                ['event_id' => $eventId],
                [
                    'event_type' => $eventType,
                    'status' => 'received',
                    'payload_hash' => hash('sha256', $payload),
                    'checkout_session_id' => $identifiers['checkout_session_id'] ?? null,
                    'payment_intent_id' => $identifiers['payment_intent_id'] ?? null,
                ]
            );

            // Si l'événement existait déjà, vérifier son statut et mettre à jour les identifiants si nécessaire
            if ($webhookEvent->wasRecentlyCreated === false) {
                // Mettre à jour les identifiants si null mais disponibles dans le payload
                $needsUpdate = false;
                $updateData = [];

                if (empty($webhookEvent->checkout_session_id) && !empty($identifiers['checkout_session_id'])) {
                    $updateData['checkout_session_id'] = $identifiers['checkout_session_id'];
                    $needsUpdate = true;
                }

                if (empty($webhookEvent->payment_intent_id) && !empty($identifiers['payment_intent_id'])) {
                    $updateData['payment_intent_id'] = $identifiers['payment_intent_id'];
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $webhookEvent->update($updateData);
                }

                // Vérifier le statut de l'événement
                // Règle 1 : Si status final (processed/ignored), ne pas dispatch
                if ($webhookEvent->isProcessed()) {
                    Log::info('Stripe webhook: Event already processed (idempotence)', [
                        'event_id' => $eventId,
                        'event_type' => $webhookEvent->event_type,
                        'status' => $webhookEvent->status,
                    ]);
                    return response()->json(['status' => 'already_processed'], 200);
                }

                // Règle 2 : Atomic claim "first dispatch" (dispatched_at IS NULL)
                $rowsAffected = DB::table('stripe_webhook_events')
                    ->where('id', $webhookEvent->id)
                    ->whereNull('dispatched_at')
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    // Claim réussi : dispatch le job
                    try {
                        ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
                        Log::info('Stripe webhook: Job dispatched (atomic claim)', [
                            'event_id' => $eventId,
                            'event_type' => $webhookEvent->event_type,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Stripe webhook: Failed to dispatch job', [
                            'event_id' => $eventId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return response()->json(['status' => 'received'], 200);
                }

                // Règle 3 : Atomic claim "redispatch failed old" (status=failed AND dispatched_at < threshold)
                $threshold = now()->subMinutes(5);
                $rowsAffected = DB::table('stripe_webhook_events')
                    ->where('id', $webhookEvent->id)
                    ->where('status', 'failed')
                    ->whereNotNull('dispatched_at')
                    ->where('dispatched_at', '<', $threshold)
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    // Claim réussi : redispatch le job
                    try {
                        ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
                        Log::info('Stripe webhook: Job redispatched (atomic claim failed old)', [
                            'event_id' => $eventId,
                            'event_type' => $webhookEvent->event_type,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Stripe webhook: Failed to redispatch job', [
                            'event_id' => $eventId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return response()->json(['status' => 'received'], 200);
                }

                // Règle 4 : Déjà dispatché récemment, ne pas redispatch
                return response()->json(['status' => 'received'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook: Failed to persist event', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to persist'], 500);
        }

        // 3. DISPATCH JOB (nouvel événement) - Atomic claim
        $rowsAffected = DB::table('stripe_webhook_events')
            ->where('id', $webhookEvent->id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected === 1) {
            try {
                ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
                Log::info('Stripe webhook: Job dispatched (new event)', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                ]);
            } catch (\Exception $e) {
                Log::error('Stripe webhook: Failed to dispatch job', [
                    'event_id' => $eventId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4. RETURN 200 vite
        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Callback Monetbil
     * 
     * Pattern v1.1 : verify → persist event → dispatch job → 200 rapide
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function monetbil(Request $request): JsonResponse
    {
        $payload = $request->all();
        $webhookSecret = config('services.monetbil.service_secret') ?? '';
        $isProduction = app()->environment('production');

        // 1. VERIFY signature/auth (OBLIGATOIRE en production)
        $signature = $request->header('X-Signature') 
                  ?? $request->header('X-Callback-Signature');
        
        if ($isProduction) {
            // En production : signature OBLIGATOIRE
            if (empty($webhookSecret)) {
                Log::error('Monetbil callback: Webhook secret not configured', [
                    'ip' => $request->ip(),
                    'reason' => 'missing_secret',
                ]);
                return response()->json(['error' => 'Configuration error'], 500);
            }

            if (empty($signature)) {
                Log::error('Monetbil callback: Missing signature in production', [
                    'ip' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                    'reason' => 'missing_signature',
                ]);
                return response()->json(['error' => 'Missing signature'], 401);
            }

            // Vérifier la signature avec hash_equals (timing-safe)
            $payloadString = $request->getContent();
            $expectedSignature = hash_hmac('sha256', $payloadString, $webhookSecret);
            
            if (!hash_equals($expectedSignature, $signature)) {
                Log::error('Monetbil callback: Invalid signature in production', [
                    'ip' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 100),
                    'reason' => 'invalid_signature',
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            Log::info('Monetbil callback: Signature verified', [
                'ip' => $request->ip(),
            ]);
        } else {
            // En développement : signature optionnelle mais recommandée
            if ($signature && $webhookSecret) {
                $payloadString = $request->getContent();
                $expectedSignature = hash_hmac('sha256', $payloadString, $webhookSecret);
                
                if (!hash_equals($expectedSignature, $signature)) {
                    Log::warning('Monetbil callback: Invalid signature in development (continuing)', [
                        'ip' => $request->ip(),
                        'reason' => 'invalid_signature_dev',
                    ]);
                    // En développement, continuer avec warning
                } else {
                    Log::info('Monetbil callback: Signature verified (development)', [
                        'ip' => $request->ip(),
                    ]);
                }
            } else {
                Log::info('Monetbil callback: Processing without signature verification (development)', [
                    'ip' => $request->ip(),
                    'has_signature' => !empty($signature),
                    'has_secret' => !empty($webhookSecret),
                ]);
            }
        }

        // Générer event_key unique (hash stable pour idempotence)
        $eventKey = $this->generateEventKey($payload);

        // 2. PERSIST EVENT (idempotent)
        try {
            $callbackEvent = MonetbilCallbackEvent::firstOrCreate(
                ['event_key' => $eventKey],
                [
                    'payment_ref' => $payload['payment_ref'] ?? $payload['item_ref'] ?? null,
                    'transaction_id' => $payload['transaction_id'] ?? null,
                    'transaction_uuid' => $payload['transaction_uuid'] ?? null,
                    'event_type' => $payload['event_type'] ?? $payload['status'] ?? null,
                    'status' => 'received',
                    'payload' => $payload,
                    'received_at' => now(),
                ]
            );

            // Si l'événement existait déjà, vérifier son statut
            if ($callbackEvent->wasRecentlyCreated === false) {
                // Règle 1 : Si status final (processed/ignored), ne pas dispatch
                if (in_array($callbackEvent->status, ['processed', 'ignored'])) {
                    Log::info('Monetbil callback: Event already processed (idempotence)', [
                        'event_key' => $eventKey,
                        'event_type' => $callbackEvent->event_type,
                        'status' => $callbackEvent->status,
                    ]);
                    return response()->json(['status' => 'already_processed'], 200);
                }

                // Règle 2 : Atomic claim "first dispatch" (dispatched_at IS NULL)
                $rowsAffected = DB::table('monetbil_callback_events')
                    ->where('id', $callbackEvent->id)
                    ->whereNull('dispatched_at')
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    // Claim réussi : dispatch le job
                    try {
                        ProcessMonetbilCallbackEventJob::dispatch($callbackEvent->id);
                        Log::info('Monetbil callback: Job dispatched (atomic claim)', [
                            'event_key' => $eventKey,
                            'event_type' => $callbackEvent->event_type,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Monetbil callback: Failed to dispatch job', [
                            'event_key' => $eventKey,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return response()->json(['status' => 'received'], 200);
                }

                // Règle 3 : Atomic claim "redispatch failed old" (status=failed AND dispatched_at < threshold)
                $threshold = now()->subMinutes(5);
                $rowsAffected = DB::table('monetbil_callback_events')
                    ->where('id', $callbackEvent->id)
                    ->where('status', 'failed')
                    ->whereNotNull('dispatched_at')
                    ->where('dispatched_at', '<', $threshold)
                    ->update([
                        'dispatched_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($rowsAffected === 1) {
                    // Claim réussi : redispatch le job
                    try {
                        ProcessMonetbilCallbackEventJob::dispatch($callbackEvent->id);
                        Log::info('Monetbil callback: Job redispatched (atomic claim failed old)', [
                            'event_key' => $eventKey,
                            'event_type' => $callbackEvent->event_type,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Monetbil callback: Failed to redispatch job', [
                            'event_key' => $eventKey,
                            'error' => $e->getMessage(),
                        ]);
                    }
                    return response()->json(['status' => 'received'], 200);
                }

                // Règle 4 : Déjà dispatché récemment, ne pas redispatch
                return response()->json(['status' => 'received'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Monetbil callback: Failed to persist event', [
                'event_key' => $eventKey,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Failed to persist'], 500);
        }

        // 3. DISPATCH JOB (nouvel événement) - Atomic claim
        $rowsAffected = DB::table('monetbil_callback_events')
            ->where('id', $callbackEvent->id)
            ->whereNull('dispatched_at')
            ->update([
                'dispatched_at' => now(),
                'updated_at' => now(),
            ]);

        if ($rowsAffected === 1) {
            try {
                ProcessMonetbilCallbackEventJob::dispatch($callbackEvent->id);
                Log::info('Monetbil callback: Job dispatched (new event)', [
                    'event_key' => $eventKey,
                    'event_type' => $callbackEvent->event_type,
                ]);
            } catch (\Exception $e) {
                Log::error('Monetbil callback: Failed to dispatch job', [
                    'event_key' => $eventKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 4. RETURN 200 vite
        return response()->json(['status' => 'received'], 200);
    }

    /**
     * Extraire les identifiants Stripe (checkout_session_id, payment_intent_id) depuis le payload
     *
     * @param array $event Événement Stripe (tableau)
     * @return array ['checkout_session_id' => string|null, 'payment_intent_id' => string|null]
     */
    private function extractStripeIdentifiers(array $event): array
    {
        $checkoutSessionId = null;
        $paymentIntentId = null;

        $eventType = $event['type'] ?? null;
        $object = $event['data']['object'] ?? null;

        if (!$object) {
            return ['checkout_session_id' => null, 'payment_intent_id' => null];
        }

        // Gérer les événements checkout.session.*
        if (str_starts_with($eventType ?? '', 'checkout.session.')) {
            // checkout_session_id = object.id (session ID)
            $checkoutSessionId = $object['id'] ?? null;

            // payment_intent_id peut être dans object.payment_intent (si présent)
            $paymentIntentId = $object['payment_intent'] ?? null;
        }

        // Gérer les événements payment_intent.*
        if (str_starts_with($eventType ?? '', 'payment_intent.')) {
            // payment_intent_id = object.id
            $paymentIntentId = $object['id'] ?? null;
        }

        return [
            'checkout_session_id' => $checkoutSessionId,
            'payment_intent_id' => $paymentIntentId,
        ];
    }

    /**
     * Générer une clé d'événement unique et stable pour idempotence
     *
     * @param array $payload
     * @return string
     */
    private function generateEventKey(array $payload): string
    {
        // Utiliser transaction_id + transaction_uuid + timestamp si disponible
        $key = ($payload['transaction_id'] ?? '') 
             . '|' . ($payload['transaction_uuid'] ?? '')
             . '|' . ($payload['payment_ref'] ?? '')
             . '|' . ($payload['timestamp'] ?? now()->timestamp);
        
        return hash('sha256', $key);
    }
}



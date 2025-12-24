<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\CreatorCapabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Contrôleur pour les webhooks Stripe Billing (abonnements créateurs).
 * 
 * Ce contrôleur :
 * - Reçoit les webhooks Stripe Billing
 * - Vérifie la signature Stripe
 * - Met à jour CreatorSubscription selon les événements
 * - Bloque automatiquement les créateurs non payants
 * - Invalide le cache des capabilities
 * 
 * ⚠️ Ce contrôleur ne fait PAS :
 * - De checkout (géré ailleurs)
 * - De Stripe Connect (géré par StripeConnectWebhookController)
 * - D'appel Stripe inutile
 * - De redirection
 * - De notification
 * 
 * 📋 Événements gérés (STRICT) :
 * - customer.subscription.created → Créer/synchroniser l'abonnement
 * - customer.subscription.updated → Mettre à jour le statut
 * - customer.subscription.deleted → Désactiver l'abonnement
 * - invoice.payment_failed → Marquer l'abonnement non actif
 * - invoice.paid → Confirmer l'abonnement actif
 */
class StripeBillingWebhookController extends Controller
{
    /**
     * Gère les webhooks Stripe Billing.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Récupérer le payload brut (important pour vérification signature Stripe)
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret') ?? '';
        $isProduction = app()->environment('production');

        // Log initial (safe)
        Log::info('received_stripe_billing_webhook', [
            'signature_header_present' => !empty($signature),
            'ip' => $request->ip(),
        ]);

        // 1. VÉRIFIER LA SIGNATURE STRIPE
        try {
            if ($isProduction && empty($signature)) {
                Log::error('Stripe Billing webhook: Missing signature in production', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Missing signature'], 400);
            }

            if ($isProduction && empty($webhookSecret)) {
                Log::error('Stripe Billing webhook: Webhook secret not configured', [
                    'ip' => $request->ip(),
                ]);
                return response()->json(['error' => 'Configuration error'], 500);
            }

            // Vérifier la signature
            if ($signature && $webhookSecret) {
                try {
                    $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
                } catch (SignatureVerificationException $e) {
                    // Si signature invalide en dev, parser quand même
                    if (!$isProduction) {
                        $event = json_decode($payload, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            return response()->json(['error' => 'Invalid JSON'], 400);
                        }
                    } else {
                        Log::error('Stripe Billing webhook: Invalid signature', [
                            'ip' => $request->ip(),
                            'error' => $e->getMessage(),
                        ]);
                        return response()->json(['error' => 'Invalid signature'], 400);
                    }
                }
            } else {
                // Dev mode : parser sans vérification
                $event = json_decode($payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return response()->json(['error' => 'Invalid JSON'], 400);
                }
            }
        } catch (SignatureVerificationException $e) {
            // En dev, ignorer l'erreur de signature et parser quand même
            if (!$isProduction) {
                $event = json_decode($payload, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('Stripe Billing webhook: Invalid JSON', [
                        'ip' => $request->ip(),
                    ]);
                    return response()->json(['error' => 'Invalid JSON'], 400);
                }
            } else {
                Log::error('Stripe Billing webhook: Invalid signature', [
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                ]);
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Stripe Billing webhook: Verification error', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Verification failed'], 400);
        }

        // Normaliser l'événement en array
        $eventArray = is_object($event) ? json_decode(json_encode($event), true) : $event;

        // Extraire event_type
        $eventType = $eventArray['type'] ?? null;

        if (empty($eventType)) {
            Log::warning('Stripe Billing webhook: Missing event type', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid event'], 400);
        }

        // Log avec event_type
        Log::info('received_stripe_billing_webhook_parsed', [
            'event_type' => $eventType,
            'ip' => $request->ip(),
        ]);

        // 2. FILTRER ET TRAITER LES ÉVÉNEMENTS
        try {
            switch ($eventType) {
                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($eventArray);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($eventArray);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($eventArray);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($eventArray);
                    break;

                case 'invoice.paid':
                    $this->handleInvoicePaid($eventArray);
                    break;

                default:
                    // Ignorer tous les autres événements
                    Log::debug('Stripe Billing webhook: Event ignored', [
                        'event_type' => $eventType,
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Stripe Billing webhook: Processing error', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Ne pas retourner d'erreur HTTP pour éviter les retries Stripe
        }

        // 3. RETOURNER 200 OK
        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Gère l'événement customer.subscription.created
     * 
     * Crée ou synchronise l'abonnement dans CreatorSubscription.
     * 
     * @param array $eventArray
     * @return void
     */
    protected function handleSubscriptionCreated(array $eventArray): void
    {
        $subscriptionObject = $eventArray['data']['object'] ?? null;
        
        if (!$subscriptionObject) {
            Log::warning('Stripe Billing webhook: Missing subscription object', [
                'event_type' => 'customer.subscription.created',
            ]);
            return;
        }

        $stripeSubscriptionId = $subscriptionObject['id'] ?? null;
        $stripeCustomerId = $subscriptionObject['customer'] ?? null;

        if (empty($stripeSubscriptionId) || empty($stripeCustomerId)) {
            Log::warning('Stripe Billing webhook: Missing subscription or customer ID', [
                'event_type' => 'customer.subscription.created',
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return;
        }

        // Trouver l'abonnement existant ou le créateur via customer_id
        $subscription = CreatorSubscription::where('stripe_subscription_id', $stripeSubscriptionId)
            ->orWhere('stripe_customer_id', $stripeCustomerId)
            ->first();

        // Si l'abonnement existe déjà, le mettre à jour
        if ($subscription) {
            $this->updateSubscriptionFromStripe($subscription, $subscriptionObject);
            Log::info('Stripe Billing webhook: Subscription synchronized (created event)', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'creator_subscription_id' => $subscription->id,
            ]);
            return;
        }

        // Sinon, chercher le créateur via metadata ou customer_id
        // Note: Le customer_id devrait être stocké dans metadata lors de la création
        $creatorId = $subscriptionObject['metadata']['creator_id'] ?? null;
        
        if (!$creatorId) {
            Log::warning('Stripe Billing webhook: Cannot find creator for subscription', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_customer_id' => $stripeCustomerId,
            ]);
            return;
        }

        $creator = User::find($creatorId);
        if (!$creator || !$creator->isCreator()) {
            Log::warning('Stripe Billing webhook: Creator not found or invalid', [
                'creator_id' => $creatorId,
                'stripe_subscription_id' => $stripeSubscriptionId,
            ]);
            return;
        }

        // Créer l'abonnement
        $subscription = $this->createSubscriptionFromStripe($creator, $subscriptionObject);
        
        if ($subscription) {
            Log::info('Stripe Billing webhook: Subscription created', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'creator_subscription_id' => $subscription->id,
                'creator_id' => $creatorId,
            ]);
        }
    }

    /**
     * Gère l'événement customer.subscription.updated
     * 
     * Met à jour le statut de l'abonnement.
     * 
     * @param array $eventArray
     * @return void
     */
    protected function handleSubscriptionUpdated(array $eventArray): void
    {
        $subscriptionObject = $eventArray['data']['object'] ?? null;
        
        if (!$subscriptionObject) {
            Log::warning('Stripe Billing webhook: Missing subscription object', [
                'event_type' => 'customer.subscription.updated',
            ]);
            return;
        }

        $stripeSubscriptionId = $subscriptionObject['id'] ?? null;

        if (empty($stripeSubscriptionId)) {
            Log::warning('Stripe Billing webhook: Missing subscription ID', [
                'event_type' => 'customer.subscription.updated',
            ]);
            return;
        }

        $subscription = CreatorSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if (!$subscription) {
            Log::warning('Stripe Billing webhook: Subscription not found', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'event_type' => 'customer.subscription.updated',
            ]);
            return;
        }

        $this->updateSubscriptionFromStripe($subscription, $subscriptionObject);
        
        Log::info('Stripe Billing webhook: Subscription updated', [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'creator_subscription_id' => $subscription->id,
            'new_status' => $subscription->status,
        ]);
    }

    /**
     * Gère l'événement customer.subscription.deleted
     * 
     * Désactive l'abonnement.
     * 
     * @param array $eventArray
     * @return void
     */
    protected function handleSubscriptionDeleted(array $eventArray): void
    {
        $subscriptionObject = $eventArray['data']['object'] ?? null;
        
        if (!$subscriptionObject) {
            Log::warning('Stripe Billing webhook: Missing subscription object', [
                'event_type' => 'customer.subscription.deleted',
            ]);
            return;
        }

        $stripeSubscriptionId = $subscriptionObject['id'] ?? null;

        if (empty($stripeSubscriptionId)) {
            Log::warning('Stripe Billing webhook: Missing subscription ID', [
                'event_type' => 'customer.subscription.deleted',
            ]);
            return;
        }

        $subscription = CreatorSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if (!$subscription) {
            Log::warning('Stripe Billing webhook: Subscription not found', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'event_type' => 'customer.subscription.deleted',
            ]);
            return;
        }

        // Mettre à jour le statut et invalider le cache
        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);

        $this->clearCreatorCache($subscription);

        Log::info('Stripe Billing webhook: Subscription deleted', [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'creator_subscription_id' => $subscription->id,
        ]);
    }

    /**
     * Gère l'événement invoice.payment_failed
     * 
     * Marque l'abonnement comme non actif (past_due ou unpaid).
     * 
     * @param array $eventArray
     * @return void
     */
    protected function handleInvoicePaymentFailed(array $eventArray): void
    {
        $invoiceObject = $eventArray['data']['object'] ?? null;
        
        if (!$invoiceObject) {
            Log::warning('Stripe Billing webhook: Missing invoice object', [
                'event_type' => 'invoice.payment_failed',
            ]);
            return;
        }

        $stripeSubscriptionId = $invoiceObject['subscription'] ?? null;

        if (empty($stripeSubscriptionId)) {
            Log::warning('Stripe Billing webhook: Missing subscription ID in invoice', [
                'event_type' => 'invoice.payment_failed',
            ]);
            return;
        }

        $subscription = CreatorSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if (!$subscription) {
            Log::warning('Stripe Billing webhook: Subscription not found for failed payment', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'event_type' => 'invoice.payment_failed',
            ]);
            return;
        }

        // Marquer comme past_due (période de grâce) ou unpaid selon le nombre d'échecs
        // Stripe envoie généralement past_due d'abord, puis unpaid après plusieurs échecs
        $attemptCount = $invoiceObject['attempt_count'] ?? 1;
        $status = $attemptCount >= 3 ? 'unpaid' : 'past_due';

        $subscription->update([
            'status' => $status,
        ]);

        $this->clearCreatorCache($subscription);

        Log::info('Stripe Billing webhook: Payment failed', [
            'stripe_subscription_id' => $stripeSubscriptionId,
            'creator_subscription_id' => $subscription->id,
            'new_status' => $status,
            'attempt_count' => $attemptCount,
        ]);
    }

    /**
     * Gère l'événement invoice.paid
     * 
     * Confirme l'abonnement actif.
     * 
     * @param array $eventArray
     * @return void
     */
    protected function handleInvoicePaid(array $eventArray): void
    {
        $invoiceObject = $eventArray['data']['object'] ?? null;
        
        if (!$invoiceObject) {
            Log::warning('Stripe Billing webhook: Missing invoice object', [
                'event_type' => 'invoice.paid',
            ]);
            return;
        }

        $stripeSubscriptionId = $invoiceObject['subscription'] ?? null;

        if (empty($stripeSubscriptionId)) {
            Log::warning('Stripe Billing webhook: Missing subscription ID in invoice', [
                'event_type' => 'invoice.paid',
            ]);
            return;
        }

        $subscription = CreatorSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if (!$subscription) {
            Log::warning('Stripe Billing webhook: Subscription not found for paid invoice', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'event_type' => 'invoice.paid',
            ]);
            return;
        }

        // Mettre à jour le statut vers active si ce n'est pas déjà le cas
        if ($subscription->status !== 'active') {
            $subscription->update([
                'status' => 'active',
            ]);

            $this->clearCreatorCache($subscription);

            Log::info('Stripe Billing webhook: Payment confirmed, subscription activated', [
                'stripe_subscription_id' => $stripeSubscriptionId,
                'creator_subscription_id' => $subscription->id,
            ]);
        } else {
            // Même si déjà actif, invalider le cache pour s'assurer que les données sont à jour
            $this->clearCreatorCache($subscription);
        }
    }

    /**
     * Crée un abonnement CreatorSubscription à partir d'un objet Stripe.
     * 
     * @param User $creator
     * @param array $subscriptionObject
     * @return CreatorSubscription|null
     */
    protected function createSubscriptionFromStripe(User $creator, array $subscriptionObject): ?CreatorSubscription
    {
        try {
            $creatorProfile = $creator->creatorProfile;
            
            if (!$creatorProfile) {
                Log::error('Stripe Billing webhook: Creator profile not found', [
                    'creator_id' => $creator->id,
                ]);
                return null;
            }

            // Extraire les données de l'abonnement Stripe
            $stripeSubscriptionId = $subscriptionObject['id'] ?? null;
            $stripeCustomerId = $subscriptionObject['customer'] ?? null;
            $stripePriceId = $subscriptionObject['items']['data'][0]['price']['id'] ?? null;
            $status = $this->mapStripeStatusToLocal($subscriptionObject['status'] ?? 'incomplete');
            
            $currentPeriodStart = isset($subscriptionObject['current_period_start']) 
                ? date('Y-m-d H:i:s', $subscriptionObject['current_period_start'])
                : now();
            
            $currentPeriodEnd = isset($subscriptionObject['current_period_end']) 
                ? date('Y-m-d H:i:s', $subscriptionObject['current_period_end'])
                : now()->addMonth();

            // Créer l'abonnement
            $subscription = CreatorSubscription::create([
                'creator_profile_id' => $creatorProfile->id,
                'creator_id' => $creator->id,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_price_id' => $stripePriceId,
                'status' => $status,
                'current_period_start' => $currentPeriodStart,
                'current_period_end' => $currentPeriodEnd,
                'started_at' => $currentPeriodStart,
                'ends_at' => $currentPeriodEnd,
                'cancel_at_period_end' => $subscriptionObject['cancel_at_period_end'] ?? false,
                'canceled_at' => isset($subscriptionObject['canceled_at']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['canceled_at'])
                    : null,
                'trial_start' => isset($subscriptionObject['trial_start']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['trial_start'])
                    : null,
                'trial_end' => isset($subscriptionObject['trial_end']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['trial_end'])
                    : null,
                'metadata' => $subscriptionObject['metadata'] ?? null,
            ]);

            // Invalider le cache
            $this->clearCreatorCache($subscription);

            return $subscription;
        } catch (\Exception $e) {
            Log::error('Stripe Billing webhook: Failed to create subscription', [
                'creator_id' => $creator->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Met à jour un abonnement CreatorSubscription à partir d'un objet Stripe.
     * 
     * @param CreatorSubscription $subscription
     * @param array $subscriptionObject
     * @return void
     */
    protected function updateSubscriptionFromStripe(CreatorSubscription $subscription, array $subscriptionObject): void
    {
        try {
            $status = $this->mapStripeStatusToLocal($subscriptionObject['status'] ?? $subscription->status);
            
            $updateData = [
                'status' => $status,
                'current_period_start' => isset($subscriptionObject['current_period_start']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['current_period_start'])
                    : $subscription->current_period_start,
                'current_period_end' => isset($subscriptionObject['current_period_end']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['current_period_end'])
                    : $subscription->current_period_end,
                'ends_at' => isset($subscriptionObject['current_period_end']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['current_period_end'])
                    : $subscription->ends_at,
                'cancel_at_period_end' => $subscriptionObject['cancel_at_period_end'] ?? $subscription->cancel_at_period_end,
                'canceled_at' => isset($subscriptionObject['canceled_at']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['canceled_at'])
                    : $subscription->canceled_at,
                'trial_start' => isset($subscriptionObject['trial_start']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['trial_start'])
                    : $subscription->trial_start,
                'trial_end' => isset($subscriptionObject['trial_end']) 
                    ? date('Y-m-d H:i:s', $subscriptionObject['trial_end'])
                    : $subscription->trial_end,
            ];

            // Mettre à jour les identifiants Stripe si nécessaire
            if (isset($subscriptionObject['customer']) && $subscriptionObject['customer'] !== $subscription->stripe_customer_id) {
                $updateData['stripe_customer_id'] = $subscriptionObject['customer'];
            }

            if (isset($subscriptionObject['items']['data'][0]['price']['id']) && 
                $subscriptionObject['items']['data'][0]['price']['id'] !== $subscription->stripe_price_id) {
                $updateData['stripe_price_id'] = $subscriptionObject['items']['data'][0]['price']['id'];
            }

            // Mettre à jour les métadonnées
            if (isset($subscriptionObject['metadata'])) {
                $updateData['metadata'] = $subscriptionObject['metadata'];
            }

            $subscription->update($updateData);

            // Invalider le cache
            $this->clearCreatorCache($subscription);
        } catch (\Exception $e) {
            Log::error('Stripe Billing webhook: Failed to update subscription', [
                'creator_subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Mappe le statut Stripe vers le statut local.
     * 
     * @param string $stripeStatus
     * @return string
     */
    protected function mapStripeStatusToLocal(string $stripeStatus): string
    {
        $mapping = [
            'incomplete' => 'incomplete',
            'incomplete_expired' => 'incomplete_expired',
            'trialing' => 'trialing',
            'active' => 'active',
            'past_due' => 'past_due',
            'canceled' => 'canceled',
            'unpaid' => 'unpaid',
        ];

        return $mapping[$stripeStatus] ?? 'incomplete';
    }

    /**
     * Invalide le cache des capabilities pour le créateur.
     * 
     * @param CreatorSubscription $subscription
     * @return void
     */
    protected function clearCreatorCache(CreatorSubscription $subscription): void
    {
        try {
            $creator = $subscription->creator;
            
            if ($creator) {
                app(CreatorCapabilityService::class)->clearCache($creator);
            }
        } catch (\Exception $e) {
            Log::warning('Stripe Billing webhook: Failed to clear cache', [
                'creator_subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}


<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use App\Services\Webhooks\CircuitBreakerService;
use App\Services\Webhooks\WebhookDeduplicationService;
use App\Services\Webhooks\WebhookRetryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

/**
 * ✅ Stripe Webhook Controller with Circuit Breaker
 * 
 * SECURITY & RESILIENCE:
 * - Circuit Breaker: rejects if too many failures
 * - Deduplication: prevents duplicate processing
 * - Signature verification: validates Stripe events
 * - Comprehensive logging: all events tracked
 */
class StripeWebhookController extends Controller
{
    private CircuitBreakerService $circuitBreaker;
    private WebhookDeduplicationService $deduplication;
    private WebhookRetryService $retryService;

    public function __construct()
    {
        $this->circuitBreaker = app(CircuitBreakerService::class);
        $this->deduplication = app(WebhookDeduplicationService::class);
        $this->retryService = app(WebhookRetryService::class);
    }

    /**
     * Main webhook handler for Stripe events
     */
    public function handle(Request $request): JsonResponse
    {
        // ✅ CIRCUIT BREAKER: Check if Stripe is failing
        if (!$this->circuitBreaker->isAvailable('stripe')) {
            Log::warning('❌ Stripe Circuit Breaker OPEN - rejecting webhook');
            return response()->json([
                'status' => 'circuit_open',
                'message' => 'Service temporarily unavailable'
            ], 503);
        }

        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        
        // ✅ SECURITY: Verify Stripe signature
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::error('❌ Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            
            return response()->json([
                'error' => 'Invalid signature'
            ], 400);
        } catch (\Exception $e) {
            Log::error('❌ Stripe webhook parsing failed', [
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'Webhook error'
            ], 400);
        }

        // ✅ DEDUPLICATION: Check if duplicate
        if ($this->deduplication->isDuplicate('stripe', $event->id)) {
            Log::info('⏭️ Duplicate Stripe webhook skipped', [
                'event_id' => $event->id,
                'type' => $event->type,
            ]);
            $this->circuitBreaker->recordSuccess('stripe');
            return response()->json(['status' => 'duplicate_skipped']);
        }
        
        Log::info('✅ Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);

        $eventDataArray = json_decode(json_encode($event->data->object ?? null), true) ?? [];
        $payload = [
            'provider' => 'stripe',
            'type' => $event->type,
            'event_id' => $event->id,
            'external_id' => $event->id,
            'event' => $event,
            'event_data' => $eventDataArray,
        ];

        $success = $this->retryService->retry(
            handler: function (array $p) {
                $ev = $p['event'];
                switch ($ev->type) {
                    case 'checkout.session.completed':
                        $this->handleCheckoutCompleted($ev->data->object);
                        break;
                    case 'customer.subscription.created':
                        $this->handleSubscriptionCreated($ev->data->object);
                        break;
                    case 'customer.subscription.updated':
                        $this->handleSubscriptionUpdated($ev->data->object);
                        break;
                    case 'customer.subscription.deleted':
                        $this->handleSubscriptionDeleted($ev->data->object);
                        break;
                    case 'invoice.payment_succeeded':
                        $this->handleInvoicePaymentSucceeded($ev->data->object);
                        break;
                    case 'invoice.payment_failed':
                        $this->handleInvoicePaymentFailed($ev->data->object);
                        break;
                    default:
                        Log::info('ℹ️ Unhandled Stripe event type', ['type' => $ev->type]);
                }
            },
            payload: $payload,
            maxAttempts: 3,
            initialDelay: 1,
            webhookId: $event->id
        );

        if ($success) {
            try {
                // ✅ EXACTLY-ONCE GUARANTEE: Mark permanently as processed
                $this->deduplication->markAsProcessedSuccess('stripe', $event->id);

                $failure = \App\Models\WebhookFailure::where('external_id', $event->id)->first();
                if ($failure) {
                    $this->deduplication->markAsProcessed($failure);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to mark webhook as processed', [
                    'webhook_id' => $event->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $this->circuitBreaker->recordSuccess('stripe');
            return response()->json(['status' => 'success']);
        }

        $this->circuitBreaker->recordFailure('stripe', 'Processing failed after retries');
        // WebhookRetryService a déjà envoyé l'événement vers la Dead Letter Queue (webhook_failures)

        return response()->json([
            'status' => 'error',
            'message' => 'Event logged but processing failed'
        ], 200);
    }
    
    /**
     * Traiter un payload depuis la Dead Letter Queue (retry)
     * Utilisé par webhook:retry-failures pour rejouer les événements Stripe créateurs
     */
    public function processFromPayload(array $payload): void
    {
        $eventType = $payload['type'] ?? $payload['event_type'] ?? null;
        $eventData = $payload['event_data'] ?? [];

        if (!$eventType) {
            throw new \InvalidArgumentException('Missing event type in payload');
        }

        $object = (object) json_decode(json_encode($eventData));

        switch ($eventType) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($object);
                break;
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($object);
                break;
            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($object);
                break;
            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($object);
                break;
            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($object);
                break;
            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($object);
                break;
            default:
                Log::info('ℹ️ Unhandled Stripe event type (retry)', ['type' => $eventType]);
        }
    }

    /**
     * Checkout Session Completed
     */
    private function handleCheckoutCompleted($session): void
    {
        Log::info('💳 Processing checkout.session.completed', [
            'session_id' => $session->id,
            'customer' => $session->customer,
        ]);
        
        // Retrieve metadata
        $userId = $session->metadata->user_id ?? null;
        $planCode = $session->metadata->plan_code ?? null;
        
        if (!$userId || !$planCode) {
            Log::warning('⚠️ Missing metadata in checkout session', [
                'session_id' => $session->id,
            ]);
            return;
        }
        
        // Retrieve plan
        $plan = CreatorPlan::where('code', $planCode)->first();
        if (!$plan) {
            Log::error('❌ Plan not found', [
                'plan_code' => $planCode,
            ]);
            return;
        }
        
        // Create or update subscription
        $subscription = CreatorSubscription::updateOrCreate(
            [
                'user_id' => $userId,
                'status' => 'active',
            ],
            [
                'plan_id' => $plan->id,
                'stripe_subscription_id' => $session->subscription,
                'stripe_customer_id' => $session->customer,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => null,
                'trial_ends_at' => null,
            ]
        );
        
        Log::info('✅ Subscription created/updated from webhook', [
            'subscription_id' => $subscription->id,
            'user_id' => $userId,
            'plan' => $planCode,
        ]);
    }
    
    /**
     * Subscription Created
     */
    private function handleSubscriptionCreated($subscription): void
    {
        Log::info('📝 Processing customer.subscription.created', [
            'subscription_id' => $subscription->id,
            'customer' => $subscription->customer,
        ]);
        
        $creatorSubscription = CreatorSubscription::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($creatorSubscription) {
            $creatorSubscription->update([
                'status' => $subscription->status,
                'starts_at' => now(),
            ]);
            
            Log::info('✅ Subscription status updated', [
                'id' => $creatorSubscription->id,
                'status' => $subscription->status,
            ]);
        }
    }
    
    /**
     * Subscription Updated
     */
    private function handleSubscriptionUpdated($subscription): void
    {
        Log::info('🔄 Processing customer.subscription.updated', [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
        ]);
        
        $creatorSubscription = CreatorSubscription::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($creatorSubscription) {
            $creatorSubscription->update([
                'status' => $subscription->status,
            ]);
            
            Log::info('✅ Subscription updated', [
                'id' => $creatorSubscription->id,
                'new_status' => $subscription->status,
            ]);
        }
    }
    
    /**
     * Subscription Deleted (cancellation)
     */
    private function handleSubscriptionDeleted($subscription): void
    {
        Log::info('🗑️ Processing customer.subscription.deleted', [
            'subscription_id' => $subscription->id,
        ]);
        
        $creatorSubscription = CreatorSubscription::where('stripe_subscription_id', $subscription->id)->first();
        
        if ($creatorSubscription) {
            $creatorSubscription->update([
                'status' => 'canceled',
                'ends_at' => now(),
            ]);
            
            Log::info('✅ Subscription canceled', [
                'id' => $creatorSubscription->id,
            ]);
        }
    }
    
    /**
     * Invoice Payment Succeeded (renewal)
     */
    private function handleInvoicePaymentSucceeded($invoice): void
    {
        Log::info('💰 Processing invoice.payment_succeeded', [
            'invoice_id' => $invoice->id,
            'subscription' => $invoice->subscription,
        ]);
        
        if ($invoice->subscription) {
            $creatorSubscription = CreatorSubscription::where('stripe_subscription_id', $invoice->subscription)->first();
            
            if ($creatorSubscription) {
                $creatorSubscription->update([
                    'status' => 'active',
                    'ends_at' => null,
                ]);
                
                Log::info('✅ Subscription renewed', [
                    'id' => $creatorSubscription->id,
                ]);
            }
        }
    }
    
    /**
     * Invoice Payment Failed
     */
    private function handleInvoicePaymentFailed($invoice): void
    {
        Log::error('❌ Processing invoice.payment_failed', [
            'invoice_id' => $invoice->id,
            'subscription' => $invoice->subscription,
        ]);
        
        if ($invoice->subscription) {
            $creatorSubscription = CreatorSubscription::where('stripe_subscription_id', $invoice->subscription)->first();
            
            if ($creatorSubscription) {
                $creatorSubscription->update([
                    'status' => 'past_due',
                ]);
                
                Log::warning('⚠️ Subscription payment failed', [
                    'id' => $creatorSubscription->id,
                ]);
            }
        }
    }
}

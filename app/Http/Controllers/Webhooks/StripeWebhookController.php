use App\Services\Webhooks\WebhookDeduplicationService;
<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\CreatorSubscription;
        $deduplicationService = app(WebhookDeduplicationService::class);
use App\Models\CreatorPlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
                $payload,
                $request->header('Stripe-Signature'),
 * ✅ C6: Contrôleur Webhook Stripe Sécurisé
 * 
 * RÈGLES DE SÉCURITÉ:
 * - Vérification obligatoire de la signature Stripe
 * - Webhook = SEULE source de vérité pour abonnements payants
 * - Logging exhaustif de tous les événements
 * - Gestion d'erreurs robuste
 */
class StripeWebhookController extends Controller
{
    /**
     * Point d'entrée principal pour tous les webhooks Stripe
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        
        // ✅ SÉCURITÉ P0: Vérification signature Stripe
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException $e) {
            Log::error('❌ Stripe webhook signature verification failed', [
        // Check if duplicate (return 200 silently)
        if ($deduplicationService->isDuplicate('stripe', $event->id)) {
            Log::info('⏭️ Duplicate Stripe webhook skipped', [
                'event_id' => $event->id,
                'type' => $event->type,
            ]);
            return response()->json(['status' => 'duplicate_skipped']);
        }
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
        
        // Log de l'événement reçu
        Log::info('✅ Stripe webhook received', [
            'type' => $event->type,
            'id' => $event->id,
        ]);
        
        // Dispatch vers le handler approprié
        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    $this->handleCheckoutCompleted($event->data->object);
                    break;
                    
                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($event->data->object);
                    break;
                    
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($event->data->object);
                    break;
                    
            $deduplicationService->recordFailure(
                'stripe',
                $event->type,
                $event->id,
                $event->data->toArray() ?? [],
                $request->header('Stripe-Signature') ?? '',
                $e->getMessage()
            );
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;
                    
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event->data->object);
                    break;
                    
                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event->data->object);
                    break;
                    
                default:
                    Log::info('ℹ️ Unhandled Stripe event type', [
        
        // Mark as processed after successful handling
        try {
            $failure = \App\Models\WebhookFailure::where('external_id', $event->id)->first();
            if ($failure) {
                $deduplicationService->markAsProcessed($failure);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to mark webhook as processed', [
                'webhook_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
                        'type' => $event->type,
                    ]);
            }
            
            return response()->json(['status' => 'success']);
            
        } catch (\Exception $e) {
            Log::error('❌ Error processing Stripe webhook', [
                'type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Retourner 200 pour éviter que Stripe ne retry indéfiniment
            // mais logger l'erreur pour investigation
            return response()->json([
                'status' => 'error',
                'message' => 'Event logged but processing failed'
            ], 200);
        }
    }
    
    /**
     * Checkout Session Completed
     * Créer l'abonnement après paiement réussi
     */
    protected function handleCheckoutCompleted($session): void
    {
        Log::info('💳 Processing checkout.session.completed', [
            'session_id' => $session->id,
            'customer' => $session->customer,
        ]);
        
        // Récupérer les metadata
        $userId = $session->metadata->user_id ?? null;
        $planCode = $session->metadata->plan_code ?? null;
        
        if (!$userId || !$planCode) {
            Log::warning('⚠️ Missing metadata in checkout session', [
                'session_id' => $session->id,
            ]);
            return;
        }
        
        // Récupérer le plan
        $plan = CreatorPlan::where('code', $planCode)->first();
        if (!$plan) {
            Log::error('❌ Plan not found', [
                'plan_code' => $planCode,
            ]);
            return;
        }
        
        // Créer ou mettre à jour l'abonnement
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
    protected function handleSubscriptionCreated($subscription): void
    {
        Log::info('📝 Processing customer.subscription.created', [
            'subscription_id' => $subscription->id,
            'customer' => $subscription->customer,
        ]);
        
        // Récupérer l'abonnement existant via stripe_subscription_id
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
    protected function handleSubscriptionUpdated($subscription): void
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
     * Subscription Deleted (annulation)
     */
    protected function handleSubscriptionDeleted($subscription): void
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
     * Invoice Payment Succeeded (renouvellement)
     */
    protected function handleInvoicePaymentSucceeded($invoice): void
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
     * Invoice Payment Failed (échec paiement)
     */
    protected function handleInvoicePaymentFailed($invoice): void
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
                
                // TODO: Envoyer notification au créateur
            }
        }
    }
}

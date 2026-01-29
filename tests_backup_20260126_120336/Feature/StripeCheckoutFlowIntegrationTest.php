<?php

namespace Tests\Feature;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\CreatorCapabilityService;
use App\Services\Payments\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests d'intégration pour le flux checkout complet
 * 
 * Phase 4.2 - Tests flux checkout complet
 */
class StripeCheckoutFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.stripe.secret', 'sk_test_fake_secret');
        Config::set('services.stripe.currency', 'XAF');
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');
    }

    /**
     * Test : Flux complet checkout → webhook → abonnement actif
     * 
     * 1. Créateur choisit un plan
     * 2. Vérification canCreatorReceivePayments()
     * 3. Création session Checkout (simulée)
     * 4. Webhook customer.subscription.created
     * 5. Webhook invoice.paid
     * 6. Vérification abonnement actif
     */
    public function test_complete_checkout_flow_creates_active_subscription(): void
    {
        // 1. Créer un créateur complet
        $user = User::factory()->create([
            'role' => 'createur',
            'status' => 'active',
            'email' => 'creator@test.com',
        ]);

        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $stripeAccount = CreatorStripeAccount::create([
            'creator_profile_id' => $creatorProfile->id,
            'stripe_account_id' => 'acct_test_1234567890',
            'account_type' => 'express',
            'charges_enabled' => true,
            'payouts_enabled' => true,
            'onboarding_status' => 'complete',
            'details_submitted' => true,
        ]);

        // Créer un abonnement actif (pour canCreatorReceivePayments)
        CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_existing_123',
            'stripe_customer_id' => 'cus_existing_123',
            'stripe_price_id' => 'price_existing_123',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $plan = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        // 2. Authentifier le créateur
        $this->actingAs($user);

        // 3. Vérifier que canCreatorReceivePayments() retourne true
        $stripeConnectService = app(StripeConnectService::class);
        $canReceivePayments = $stripeConnectService->canCreatorReceivePayments($creatorProfile);
        $this->assertTrue($canReceivePayments, 'Le créateur doit pouvoir recevoir des paiements');

        // 4. Simuler la création d'une session Checkout
        // (En test réel, cela créerait une vraie session Stripe)
        $subscriptionId = 'sub_test_new_' . time();
        $customerId = 'cus_test_new_' . time();
        $priceId = 'price_test_new_' . time();

        // 5. Simuler le webhook customer.subscription.created
        $payload = [
            'id' => 'evt_test_subscription_created',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => $subscriptionId,
                    'customer' => $customerId,
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + 2592000,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => $priceId,
                                ],
                            ],
                        ],
                    ],
                    'metadata' => [
                        'creator_id' => $user->id,
                    ],
                ],
            ],
        ];

        $signature = $this->generateStripeSignature(json_encode($payload), 'whsec_test_secret');

        $response = $this->postJson('/api/webhooks/stripe/billing', $payload, [
            'Stripe-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // 6. Vérifier que l'abonnement a été créé
        $this->assertDatabaseHas('creator_subscriptions', [
            'stripe_subscription_id' => $subscriptionId,
            'stripe_customer_id' => $customerId,
            'status' => 'active',
        ]);

        // 7. Simuler le webhook invoice.paid
        $invoicePayload = [
            'id' => 'evt_test_invoice_paid',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'subscription' => $subscriptionId,
                ],
            ],
        ];

        $invoiceSignature = $this->generateStripeSignature(json_encode($invoicePayload), 'whsec_test_secret');

        $invoiceResponse = $this->postJson('/api/webhooks/stripe/billing', $invoicePayload, [
            'Stripe-Signature' => $invoiceSignature,
        ]);

        $invoiceResponse->assertStatus(200);

        // 8. Vérifier que l'abonnement est toujours actif
        $subscription = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('active', $subscription->status);

        // 9. Vérifier que le cache est invalidé
        $capabilityService = app(CreatorCapabilityService::class);
        $activeSubscription = $capabilityService->getActiveSubscription($user);
        $this->assertNotNull($activeSubscription);
        $this->assertEquals($subscriptionId, $activeSubscription->stripe_subscription_id);
    }

    /**
     * Helper pour générer une signature Stripe valide
     */
    private function generateStripeSignature(string $payload, string $secret, int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$signature}";
    }
}


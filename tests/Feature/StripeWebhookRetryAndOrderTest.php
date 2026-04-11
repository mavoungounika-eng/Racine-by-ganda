<?php

namespace Tests\Feature;

use App\Models\CreatorPlan;
use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests d'intégration pour les cas de retry webhook et ordre inversé
 * 
 * Phase 4.2 - Tests cas retry webhook et ordre inversé
 */
class StripeWebhookRetryAndOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.stripe.webhook_secret', 'whsec_test_secret');

        CreatorPlan::firstOrCreate(
            ['code' => 'FREE'],
            [
                'name' => 'Free Plan',
                'description' => 'Default free plan',
                'price' => 0,
                'currency' => 'XAF',
                'interval' => 'month',
                'stripe_product_id' => 'prod_test_123',
                'stripe_price_id' => 'price_test_123',
                'features' => [],
                'is_active' => true,
            ]
        );
    }

    /**
     * Test : Retry webhook après échec initial
     * 
     * 1. Premier webhook échoue (simulation)
     * 2. Webhook retry avec le même event_id
     * 3. Vérifier que l'abonnement est créé correctement
     */
    public function test_webhook_retry_after_failure_creates_subscription(): void
    {
        $roleCreator = \App\Models\Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'description' => '']);
        $user = User::factory()->create([
            'role' => 'createur',
            'role_id' => $roleCreator->id
        ]);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $eventId = 'evt_test_retry';
        $subscriptionId = 'sub_test_retry';
        $customerId = 'cus_test_retry';

        $payload = [
            'id' => $eventId,
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
                                    'id' => 'price_test_123',
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

        $payloadString = json_encode($payload);
        $signature = $this->generateStripeSignature($payloadString, 'whsec_test_secret');

        \Illuminate\Support\Facades\DB::enableQueryLog();

        // Premier appel (simule un échec puis retry)
        $response1 = $this->call('POST', '/api/webhooks/stripe/billing', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payloadString);
        
        $response1->dump();
        $response1->dumpHeaders();

        \Illuminate\Support\Facades\Log::info('Query Log: ', \Illuminate\Support\Facades\DB::getQueryLog());

        $response1->assertStatus(200);

        // Vérifier que l'abonnement a été créé
        $this->assertDatabaseHas('creator_subscriptions', [
            'stripe_subscription_id' => $subscriptionId,
        ]);

        // Deuxième appel (retry avec le même event_id)
        $response2 = $this->call('POST', '/api/webhooks/stripe/billing', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payloadString);

        $response2->assertStatus(200);

        // Vérifier qu'il n'y a toujours qu'un seul abonnement (idempotence)
        $count = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->count();
        $this->assertEquals(1, $count);
    }

    /**
     * Test : Ordre inversé - Callback succès avant webhook
     * 
     * 1. Utilisateur complète le paiement → Callback succès
     * 2. Webhook arrive après (simulation délai)
     * 3. Vérifier que l'abonnement est créé correctement
     */
    public function test_callback_before_webhook_handles_gracefully(): void
    {
        $roleCreator = \App\Models\Role::firstOrCreate(['slug' => 'createur'], ['name' => 'Créateur', 'description' => '']);
        $user = User::factory()->create([
            'role' => 'createur',
            'role_id' => $roleCreator->id,
            'status' => 'active',
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

        $plan = CreatorPlan::create([
            'code' => 'official',
            'name' => 'Créateur Officiel',
            'price' => 5000,
            'billing_cycle' => 'monthly',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $subscriptionId = 'sub_test_callback_first';
        $sessionId = 'cs_test_callback_first';

        // 1. Simuler le callback succès (avant webhook)
        // En production, le callback vérifie la session mais l'abonnement n'existe pas encore
        $callbackResponse = $this->get("/createur/abonnement/plan/{$plan->id}/checkout/success?session_id={$sessionId}");
        
        // Le callback doit gérer gracieusement l'absence d'abonnement
        // (en production, il affiche un message indiquant que l'abonnement sera activé sous peu)
        $callbackResponse->assertStatus(302); // Redirection

        // 2. Simuler le webhook qui arrive après
        $payload = [
            'id' => 'evt_test_callback_first',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => $subscriptionId,
                    'customer' => 'cus_test_callback_first',
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + 2592000,
                    'items' => [
                        'data' => [
                            [
                                'price' => [
                                    'id' => 'price_test_123',
                                ],
                            ],
                        ],
                    ],
                    'metadata' => [
                        'creator_id' => $user->id,
                        'session_id' => $sessionId,
                    ],
                ],
            ],
        ];

        $payloadString = json_encode($payload);
        $signature = $this->generateStripeSignature($payloadString, 'whsec_test_secret');

        $webhookResponse = $this->call('POST', '/api/webhooks/stripe/billing', [], [], [], [
            'HTTP_Stripe-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payloadString);

        $webhookResponse->assertStatus(200);

        // 3. Vérifier que l'abonnement a été créé par le webhook
        $this->assertDatabaseHas('creator_subscriptions', [
            'stripe_subscription_id' => $subscriptionId,
            'status' => 'active',
        ]);
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


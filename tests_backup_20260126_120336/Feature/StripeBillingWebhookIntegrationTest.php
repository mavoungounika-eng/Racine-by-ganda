<?php

namespace Tests\Feature;

use App\Models\CreatorProfile;
use App\Models\CreatorStripeAccount;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests d'intégration pour les webhooks Stripe Billing
 * 
 * Phase 4.2 - Tests d'intégration (Stripe simulé)
 */
class StripeBillingWebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        Log::fake();
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

    /**
     * Créer un payload d'événement Stripe Billing
     */
    private function createBillingEventPayload(string $eventId, string $eventType, array $objectData): string
    {
        return json_encode([
            'id' => $eventId,
            'type' => $eventType,
            'data' => [
                'object' => $objectData,
            ],
        ]);
    }

    /**
     * Test : customer.subscription.created crée l'abonnement
     */
    public function test_customer_subscription_created_creates_subscription(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $eventId = 'evt_test_subscription_created';
        $subscriptionId = 'sub_test_1234567890';
        $customerId = 'cus_test_1234567890';
        $priceId = 'price_test_1234567890';

        $payload = $this->createBillingEventPayload($eventId, 'customer.subscription.created', [
            'id' => $subscriptionId,
            'customer' => $customerId,
            'status' => 'active',
            'current_period_start' => time(),
            'current_period_end' => time() + 2592000, // +30 jours
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
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        $response = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        // Vérifier que l'abonnement a été créé
        $this->assertDatabaseHas('creator_subscriptions', [
            'stripe_subscription_id' => $subscriptionId,
            'stripe_customer_id' => $customerId,
            'status' => 'active',
        ]);
    }

    /**
     * Test : customer.subscription.updated met à jour l'abonnement
     */
    public function test_customer_subscription_updated_updates_subscription(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $subscription = CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'stripe_customer_id' => 'cus_test_123',
            'stripe_price_id' => 'price_test_123',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $eventId = 'evt_test_subscription_updated';
        $payload = $this->createBillingEventPayload($eventId, 'customer.subscription.updated', [
            'id' => 'sub_test_123',
            'status' => 'past_due',
            'current_period_start' => time(),
            'current_period_end' => time() + 2592000,
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        $response = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertEquals('past_due', $subscription->status);
    }

    /**
     * Test : invoice.payment_failed met à jour le statut
     */
    public function test_invoice_payment_failed_updates_status(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $subscription = CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'stripe_customer_id' => 'cus_test_123',
            'stripe_price_id' => 'price_test_123',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $eventId = 'evt_test_payment_failed';
        $payload = $this->createBillingEventPayload($eventId, 'invoice.payment_failed', [
            'subscription' => 'sub_test_123',
            'attempt_count' => 3,
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        $response = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertEquals('unpaid', $subscription->status);
    }

    /**
     * Test : invoice.paid active l'abonnement
     */
    public function test_invoice_paid_activates_subscription(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $subscription = CreatorSubscription::create([
            'creator_profile_id' => $creatorProfile->id,
            'creator_id' => $user->id,
            'stripe_subscription_id' => 'sub_test_123',
            'stripe_customer_id' => 'cus_test_123',
            'stripe_price_id' => 'price_test_123',
            'status' => 'past_due',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $eventId = 'evt_test_invoice_paid';
        $payload = $this->createBillingEventPayload($eventId, 'invoice.paid', [
            'subscription' => 'sub_test_123',
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        $response = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature,
        ]);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
    }

    /**
     * Test : idempotence - rejouer le même événement plusieurs fois
     */
    public function test_webhook_is_idempotent_for_same_event_id(): void
    {
        $user = User::factory()->create(['role' => 'createur']);
        $creatorProfile = CreatorProfile::create([
            'user_id' => $user->id,
            'brand_name' => 'Test Creator',
            'slug' => 'test-creator',
            'is_active' => true,
            'status' => 'active',
        ]);

        $eventId = 'evt_test_idempotent';
        $subscriptionId = 'sub_test_idempotent';
        $customerId = 'cus_test_idempotent';

        $payload = $this->createBillingEventPayload($eventId, 'customer.subscription.created', [
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
        ]);

        $signature = $this->generateStripeSignature($payload, 'whsec_test_secret');

        // Premier appel
        $response1 = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature,
        ]);
        $response1->assertStatus(200);

        $count1 = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->count();
        $this->assertEquals(1, $count1);

        // Deuxième appel (même event_id)
        $response2 = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature,
        ]);
        $response2->assertStatus(200);

        // Vérifier qu'il n'y a toujours qu'un seul abonnement
        $count2 = CreatorSubscription::where('stripe_subscription_id', $subscriptionId)->count();
        $this->assertEquals(1, $count2);
    }
}


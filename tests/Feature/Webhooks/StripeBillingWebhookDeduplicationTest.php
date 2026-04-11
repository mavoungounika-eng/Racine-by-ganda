<?php

namespace Tests\Feature\Webhooks;

use App\Models\CreatorSubscription;
use App\Models\User;
use App\Services\Webhooks\WebhookDeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use Stripe\Event as StripeEvent;

class StripeBillingWebhookDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure secret is set
        config(['services.stripe.webhook_secret' => 'whsec_test_secret']);
        
        // Clear caches
        app(WebhookDeduplicationService::class)->clearCache();
        \App\Models\ProcessedWebhook::truncate();
    }

    protected function createValidSignature(string $payload, string $secret): string
    {
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        
        return "t={$timestamp},v1={$signature}";
    }

    /** @test */
    public function it_deduplicates_identical_billing_webhooks()
    {
        // 1. Setup Creator and Subscription
        $creator = User::factory()->create(['role' => 'createur']);
        \App\Models\CreatorProfile::factory()->create(['user_id' => $creator->id]);
        
        $subscription = CreatorSubscription::factory()->create([
            'creator_id' => $creator->id,
            'stripe_subscription_id' => 'sub_123',
            'status' => 'past_due'
        ]);

        // 2. Prepare payload for invoice.paid
        $payload = json_encode([
            'id' => 'evt_12345', // The duplicated event ID
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_123',
                    'subscription' => 'sub_123',
                ]
            ]
        ]);

        $signature = $this->createValidSignature($payload, 'whsec_test_secret');

        // 3. First request - should be processed
        $response1 = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature
        ]);

        $response1->assertStatus(200);
        $this->assertEquals('ok', $response1->json('status'));

        // Verify state changed
        $this->assertEquals('active', $subscription->fresh()->status);
        
        // Verify it was logged in ProcessedWebhook
        $this->assertDatabaseHas('processed_webhooks', [
            'provider' => 'stripe_billing',
            'external_id' => 'evt_12345'
        ]);

        // 4. Second request - identical payload (should be skipped)
        $response2 = $this->postJson('/api/webhooks/stripe/billing', json_decode($payload, true), [
            'Stripe-Signature' => $signature
        ]);

        $response2->assertStatus(200);
        $this->assertEquals('duplicate_skipped', $response2->json('status'));
    }
}

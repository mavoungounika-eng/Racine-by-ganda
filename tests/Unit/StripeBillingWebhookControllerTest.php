<?php

namespace Tests\Unit;

use App\Http\Controllers\Webhooks\StripeBillingWebhookController;
use App\Models\CreatorProfile;
use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Tests unitaires pour le mapping des statuts Billing
 * 
 * Phase 4.1 - Tests unitaires (ZÉRO mock Stripe, uniquement payloads simulés)
 */
class StripeBillingWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected StripeBillingWebhookController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = app(StripeBillingWebhookController::class);
    }

    /**
     * Test : mapStripeStatusToLocal() mappe correctement tous les statuts
     */
    public function test_mapStripeStatusToLocal_maps_all_statuses_correctly(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('mapStripeStatusToLocal');
        $method->setAccessible(true);

        // Test tous les statuts
        $this->assertEquals('incomplete', $method->invoke($this->controller, 'incomplete'));
        $this->assertEquals('incomplete_expired', $method->invoke($this->controller, 'incomplete_expired'));
        $this->assertEquals('trialing', $method->invoke($this->controller, 'trialing'));
        $this->assertEquals('active', $method->invoke($this->controller, 'active'));
        $this->assertEquals('past_due', $method->invoke($this->controller, 'past_due'));
        $this->assertEquals('canceled', $method->invoke($this->controller, 'canceled'));
        $this->assertEquals('unpaid', $method->invoke($this->controller, 'unpaid'));
        
        // Test statut inconnu → fallback vers incomplete
        $this->assertEquals('incomplete', $method->invoke($this->controller, 'unknown_status'));
    }

    /**
     * Test : handleInvoicePaymentFailed() met à jour le statut selon attempt_count
     */
    public function test_handleInvoicePaymentFailed_updates_status_based_on_attempt_count(): void
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

        // Simuler un payload invoice.payment_failed avec 1-2 échecs → past_due
        $eventArray = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'subscription' => 'sub_test_123',
                    'attempt_count' => 2,
                ],
            ],
        ];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('handleInvoicePaymentFailed');
        $method->setAccessible(true);
        $method->invoke($this->controller, $eventArray);

        $subscription->refresh();
        $this->assertEquals('past_due', $subscription->status);

        // Simuler un payload avec 3+ échecs → unpaid
        $subscription->update(['status' => 'active']);
        $eventArray['data']['object']['attempt_count'] = 3;
        $method->invoke($this->controller, $eventArray);

        $subscription->refresh();
        $this->assertEquals('unpaid', $subscription->status);
    }
}


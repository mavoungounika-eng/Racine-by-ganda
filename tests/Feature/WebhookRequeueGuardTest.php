<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\WebhookRequeueGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookRequeueGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que canRequeueStripe retourne false si event processed
     */
    public function test_can_requeue_stripe_returns_false_if_processed(): void
    {
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->assertFalse(WebhookRequeueGuard::canRequeueStripe($event));
    }

    /**
     * Test que canRequeueStripe retourne true si requeue_count < 5
     */
    public function test_can_requeue_stripe_returns_true_if_count_under_limit(): void
    {
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'requeue_count' => 3,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->assertTrue(WebhookRequeueGuard::canRequeueStripe($event));
    }

    /**
     * Test que canRequeueStripe retourne false si limite atteinte et cooldown actif
     */
    public function test_can_requeue_stripe_returns_false_if_limit_reached_and_cooldown_active(): void
    {
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'requeue_count' => 5,
            'last_requeue_at' => now()->subMinutes(30), // < 1 heure
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->assertFalse(WebhookRequeueGuard::canRequeueStripe($event));
    }

    /**
     * Test que canRequeueStripe retourne true si limite atteinte mais cooldown expiré
     */
    public function test_can_requeue_stripe_returns_true_if_limit_reached_but_cooldown_expired(): void
    {
        $event = StripeWebhookEvent::create([
            'event_id' => 'evt_test',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'requeue_count' => 5,
            'last_requeue_at' => now()->subHours(2), // > 1 heure
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $this->assertTrue(WebhookRequeueGuard::canRequeueStripe($event));
    }

    /**
     * Test que getNextRequeueAt retourne null si requeue possible maintenant
     */
    public function test_get_next_requeue_at_returns_null_if_requeue_possible_now(): void
    {
        $nextAt = WebhookRequeueGuard::getNextRequeueAt(3, null);
        $this->assertNull($nextAt);
    }

    /**
     * Test que getNextRequeueAt retourne la date de déblocage si bloqué
     */
    public function test_get_next_requeue_at_returns_unlock_time_if_blocked(): void
    {
        $lastRequeueAt = now()->subMinutes(30);
        $nextAt = WebhookRequeueGuard::getNextRequeueAt(5, $lastRequeueAt);
        
        $this->assertNotNull($nextAt);
        $this->assertEquals($lastRequeueAt->copy()->addHour()->format('Y-m-d H:i'), $nextAt->format('Y-m-d H:i'));
    }

    /**
     * Test que getBlockedMessage retourne un message explicite
     */
    public function test_get_blocked_message_returns_explicit_message(): void
    {
        $lastRequeueAt = now()->subMinutes(30);
        $message = WebhookRequeueGuard::getBlockedMessage(5, $lastRequeueAt);
        
        $this->assertNotEmpty($message);
        $this->assertStringContainsString('Limite atteinte', $message);
        $this->assertStringContainsString('5', $message);
    }

    /**
     * Test que canRequeueMonetbil fonctionne comme canRequeueStripe
     */
    public function test_can_requeue_monetbil_works_like_stripe(): void
    {
        $event = MonetbilCallbackEvent::create([
            'event_key' => 'test_key',
            'status' => 'received',
            'requeue_count' => 3,
            'payload' => [],
        ]);

        $this->assertTrue(WebhookRequeueGuard::canRequeueMonetbil($event));

        $event->update(['requeue_count' => 5, 'last_requeue_at' => now()->subMinutes(30)]);
        $this->assertFalse(WebhookRequeueGuard::canRequeueMonetbil($event));
    }
}





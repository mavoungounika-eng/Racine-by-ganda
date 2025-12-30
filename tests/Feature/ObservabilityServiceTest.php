<?php

namespace Tests\Feature;

use App\Models\MonetbilCallbackEvent;
use App\Models\StripeWebhookEvent;
use App\Services\Payments\WebhookObservabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que getSummary retourne des stuck_counts cohérents
     */
    public function test_get_summary_returns_coherent_stuck_counts(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer des events stuck Stripe
        StripeWebhookEvent::create([
            'event_id' => 'evt_stuck_null',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'dispatched_at' => null,
            'payload_hash' => hash('sha256', 'test'),
        ]);

        StripeWebhookEvent::create([
            'event_id' => 'evt_stuck_failed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'failed',
            'dispatched_at' => now()->subMinutes(20), // > 10 min threshold
            'payload_hash' => hash('sha256', 'test'),
        ]);

        // Créer des events stuck Monetbil
        MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_monetbil_null'),
            'payment_ref' => 'PAY_TEST',
            'transaction_id' => 'TXN_TEST',
            'status' => 'received',
            'dispatched_at' => null,
            'payload' => ['test' => 'data'],
            'received_at' => now(),
        ]);

        MonetbilCallbackEvent::create([
            'event_key' => hash('sha256', 'test_monetbil_failed'),
            'payment_ref' => 'PAY_TEST2',
            'transaction_id' => 'TXN_TEST2',
            'status' => 'failed',
            'dispatched_at' => now()->subMinutes(20), // > 10 min threshold
            'payload' => ['test' => 'data'],
            'received_at' => now(),
        ]);

        // Créer des events non-stuck (processed)
        StripeWebhookEvent::create([
            'event_id' => 'evt_processed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'dispatched_at' => now()->subMinutes(5),
            'processed_at' => now()->subMinutes(5),
            'payload_hash' => hash('sha256', 'test'),
        ]);

        $service = app(WebhookObservabilityService::class);
        $summary = $service->getSummary([
            'window_minutes' => 60,
            'threshold_minutes' => 10,
        ]);

        // Vérifier stuck_counts
        $this->assertEquals(2, $summary['stuck_counts']['stripe']['total']);
        $this->assertEquals(1, $summary['stuck_counts']['stripe']['null_dispatched_at']);
        $this->assertEquals(1, $summary['stuck_counts']['stripe']['failed_old']);

        $this->assertEquals(2, $summary['stuck_counts']['monetbil']['total']);
        $this->assertEquals(1, $summary['stuck_counts']['monetbil']['null_dispatched_at']);
        $this->assertEquals(1, $summary['stuck_counts']['monetbil']['failed_old']);

        $this->assertEquals(4, $summary['stuck_counts']['total']);
    }

    /**
     * Test que getSummary retourne des counts_by_status
     */
    public function test_get_summary_returns_counts_by_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2025-12-15 10:00:00'));

        // Créer des events avec différents status
        StripeWebhookEvent::create([
            'event_id' => 'evt_received',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'received',
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(30),
        ]);

        StripeWebhookEvent::create([
            'event_id' => 'evt_processed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'processed',
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(30),
        ]);

        StripeWebhookEvent::create([
            'event_id' => 'evt_failed',
            'event_type' => 'payment_intent.succeeded',
            'status' => 'failed',
            'payload_hash' => hash('sha256', 'test'),
            'created_at' => now()->subMinutes(30),
        ]);

        $service = app(WebhookObservabilityService::class);
        $summary = $service->getSummary([
            'window_minutes' => 60,
            'threshold_minutes' => 10,
        ]);

        $this->assertArrayHasKey('received', $summary['counts_by_status']['stripe']);
        $this->assertArrayHasKey('processed', $summary['counts_by_status']['stripe']);
        $this->assertArrayHasKey('failed', $summary['counts_by_status']['stripe']);
    }
}





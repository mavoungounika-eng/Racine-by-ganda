<?php

namespace Tests\Feature\WebhookDeduplication;

use App\Models\WebhookFailure;
use App\Services\Webhooks\WebhookDeduplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookDeduplicationService $deduplicationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deduplicationService = app(WebhookDeduplicationService::class);
    }

    /**
     * Test: First webhook is not marked as duplicate
     */
    public function test_first_webhook_is_not_duplicate(): void
    {
        $isDuplicate = $this->deduplicationService->isDuplicate('stripe', 'evt_12345');
        
        $this->assertFalse($isDuplicate);
    }

    /**
     * Test: Second webhook with same external ID is marked as duplicate
     */
    public function test_duplicate_webhook_is_detected(): void
    {
        $this->deduplicationService->isDuplicate('stripe', 'evt_12345');
        $isDuplicate = $this->deduplicationService->isDuplicate('stripe', 'evt_12345');
        
        $this->assertTrue($isDuplicate);
    }

    /**
     * Test: Webhooks from different providers don't interfere
     */
    public function test_different_providers_dont_interfere(): void
    {
        $this->deduplicationService->isDuplicate('stripe', 'evt_12345');
        $isDuplicate = $this->deduplicationService->isDuplicate('monetbil', 'evt_12345');
        
        $this->assertFalse($isDuplicate);
    }

    /**
     * Test: Webhooks with different external IDs don't interfere
     */
    public function test_different_external_ids_dont_interfere(): void
    {
        $this->deduplicationService->isDuplicate('stripe', 'evt_12345');
        $isDuplicate = $this->deduplicationService->isDuplicate('stripe', 'evt_67890');
        
        $this->assertFalse($isDuplicate);
    }

    /**
     * Test: Record webhook failure
     */
    public function test_can_record_webhook_failure(): void
    {
        $payload = ['charge_id' => 'ch_123', 'amount' => 10000];
        $signature = 'sig_valid_signature';
        $errorMessage = 'Payment not found';

        $failure = $this->deduplicationService->recordFailure(
            'stripe',
            'charge.refunded',
            'evt_12345',
            $payload,
            $signature,
            $errorMessage
        );

        $this->assertDatabaseHas('webhook_failures', [
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'status' => 'failed',
            'retry_count' => 1,
            'error_message' => $errorMessage,
        ]);

        $this->assertEquals('stripe', $failure->provider);
        $this->assertEquals($payload, $failure->payload);
    }

    /**
     * Test: Mark webhook as processed
     */
    public function test_can_mark_webhook_as_processed(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => [],
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        $this->deduplicationService->markAsProcessed($failure);

        $failure->refresh();
        $this->assertEquals('processed', $failure->status);
    }

    /**
     * Test: Mark webhook for retry
     */
    public function test_can_mark_webhook_for_retry(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => [],
            'status' => 'pending',
            'retry_count' => 0,
        ]);

        $this->deduplicationService->markForRetry($failure, 'Temporary error');

        $failure->refresh();
        $this->assertEquals('failed', $failure->status);
        $this->assertEquals(1, $failure->retry_count);
        $this->assertNotNull($failure->last_retry_at);
    }

    /**
     * Test: Get pending failures
     */
    public function test_can_get_pending_failures(): void
    {
        WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_1',
            'payload' => [],
            'status' => 'pending',
        ]);

        WebhookFailure::create([
            'provider' => 'monetbil',
            'event_type' => 'success',
            'external_id' => 'evt_2',
            'payload' => [],
            'status' => 'processed',
        ]);

        $pending = $this->deduplicationService->getPendingFailures();

        $this->assertEquals(1, $pending->count());
        $this->assertEquals('stripe', $pending->first()->provider);
    }

    /**
     * Test: Get dead letter failures
     */
    public function test_can_get_dead_letter_failures(): void
    {
        WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_1',
            'payload' => [],
            'status' => 'dead_letter',
            'retry_count' => 3,
        ]);

        WebhookFailure::create([
            'provider' => 'monetbil',
            'event_type' => 'success',
            'external_id' => 'evt_2',
            'payload' => [],
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $deadLetter = $this->deduplicationService->getDeadLetterFailures();

        $this->assertEquals(1, $deadLetter->count());
        $this->assertEquals('stripe', $deadLetter->first()->provider);
    }

    /**
     * Test: Get statistics
     */
    public function test_can_get_statistics(): void
    {
        WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_1',
            'payload' => [],
            'status' => 'pending',
        ]);

        WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_2',
            'payload' => [],
            'status' => 'dead_letter',
            'retry_count' => 3,
        ]);

        WebhookFailure::create([
            'provider' => 'monetbil',
            'event_type' => 'success',
            'external_id' => 'evt_3',
            'payload' => [],
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $stats = $this->deduplicationService->getStatistics();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['dead_letter']);
        $this->assertArrayHasKey('stripe', $stats['by_provider']);
    }

    /**
     * Test: Retry webhook with success
     */
    public function test_can_retry_webhook_successfully(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => ['charge_id' => 'ch_123'],
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $called = false;
        $result = $this->deduplicationService->retry($failure, function ($payload) use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
        $this->assertTrue($result);
        
        $failure->refresh();
        $this->assertEquals('processed', $failure->status);
    }

    /**
     * Test: Retry webhook that exceeds max attempts
     */
    public function test_cannot_retry_webhook_exceeding_max_attempts(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => [],
            'status' => 'dead_letter',
            'retry_count' => 3,
        ]);

        $result = $this->deduplicationService->retry($failure, function () {});

        $this->assertFalse($result);
        $this->assertEquals('dead_letter', $failure->status);
    }

    /**
     * Test: Retry webhook with exception
     */
    public function test_retry_webhook_with_exception(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => [],
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $result = $this->deduplicationService->retry($failure, function () {
            throw new \Exception('Retry failed');
        });

        $this->assertFalse($result);
        
        $failure->refresh();
        $this->assertEquals('failed', $failure->status);
        $this->assertEquals(2, $failure->retry_count);
    }

    /**
     * Test: Webhook model can retry
     */
    public function test_webhook_failure_model_can_retry(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => [],
            'status' => 'failed',
            'retry_count' => 1,
        ]);

        $this->assertTrue($failure->canRetry());

        // After 3 retries, cannot retry
        $failure->update(['retry_count' => 3, 'status' => 'dead_letter']);
        $this->assertFalse($failure->canRetry());
    }

    /**
     * Test: Get event description
     */
    public function test_can_get_event_description(): void
    {
        $failure = WebhookFailure::create([
            'provider' => 'stripe',
            'event_type' => 'charge.refunded',
            'external_id' => 'evt_12345',
            'payload' => [],
            'retry_count' => 2,
        ]);

        $description = $failure->getEventDescription();

        $this->assertStringContainsString('STRIPE', $description);
        $this->assertStringContainsString('charge.refunded', $description);
        $this->assertStringContainsString('retry #2', $description);
    }

    /**
     * Test: Cache TTL is respected
     */
    public function test_cache_ttl_is_respected(): void
    {
        $this->deduplicationService->isDuplicate('stripe', 'evt_12345');
        
        // Should be duplicate now
        $this->assertTrue($this->deduplicationService->isDuplicate('stripe', 'evt_12345'));

        // Clear cache
        $this->deduplicationService->clearCache();

        // Should not be duplicate after cache clear (for non-permanent webhooks)
        $this->assertFalse($this->deduplicationService->isDuplicate('stripe', 'evt_12345'));
    }

    /**
     * Test: Permanent storage resists cache clears (Exactly-once)
     */
    public function test_webhook_persists_in_database_and_resists_cache_clear(): void
    {
        // Marquer comme traité avec succès
        $this->deduplicationService->markAsProcessedSuccess('stripe', 'evt_permanent_123');
        
        // Vider le cache pour simuler l'amnésie
        $this->deduplicationService->clearCache();
        
        // La base de données doit s'en souvenir indéfiniment
        $this->assertTrue($this->deduplicationService->isDuplicate('stripe', 'evt_permanent_123'));
        
        $this->assertDatabaseHas('processed_webhooks', [
            'provider' => 'stripe',
            'external_id' => 'evt_permanent_123'
        ]);
    }
}

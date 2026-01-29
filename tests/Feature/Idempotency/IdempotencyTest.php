<?php

namespace Tests\Feature\Idempotency;

use App\Models\IdempotencyKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Missing idempotency key returns 400
     */
    public function test_missing_idempotency_key_returns_400(): void
    {
        $this->postJson('/api/test-idempotency', [
            'name' => 'Test',
        ])->assertStatus(400)
         ->assertJsonFragment(['error' => 'Missing X-Idempotency-Key header']);
    }

    /**
     * Test: First request with idempotency key is processed
     */
    public function test_first_request_with_key_is_processed(): void
    {
        $key = 'test-key-' . now()->timestamp;

        $response = $this->postJson('/api/test-idempotency', [
            'name' => 'Test Product',
        ], [
            'X-Idempotency-Key' => $key,
        ]);

        $response->assertStatus(200)
                ->assertJsonFragment(['success' => true]);

        // Verify idempotency key was stored
        $this->assertDatabaseHas('idempotency_keys', [
            'key' => $key,
            'status' => 'completed',
        ]);
    }

    /**
     * Test: Duplicate request with same key returns cached response
     */
    public function test_duplicate_request_returns_cached_response(): void
    {
        $key = 'test-key-duplicate-' . now()->timestamp;

        // First request
        $response1 = $this->postJson('/api/test-idempotency', [
            'name' => 'Test Product',
        ], [
            'X-Idempotency-Key' => $key,
        ]);

        $response1->assertStatus(200);
        $data1 = $response1->json();

        // Second request with same key
        $response2 = $this->postJson('/api/test-idempotency', [
            'name' => 'Different Name',  // Different data, but same key
        ], [
            'X-Idempotency-Key' => $key,
        ]);

        $response2->assertStatus(200);
        $data2 = $response2->json();

        // Responses should be identical
        $this->assertEquals($data1, $data2);
    }

    /**
     * Test: Processing request returns 409 if already processing
     */
    public function test_request_in_progress_returns_409(): void
    {
        $key = 'test-key-processing-' . now()->timestamp;

        // Create a record in "processing" state
        IdempotencyKey::create([
            'key' => $key,
            'status' => 'processing',
        ]);

        // Try to submit same key
        $response = $this->postJson('/api/test-idempotency', [
            'name' => 'Test Product',
        ], [
            'X-Idempotency-Key' => $key,
        ]);

        $response->assertStatus(409)
                ->assertJsonFragment(['error' => 'Request is already being processed']);
    }

    /**
     * Test: Different keys are processed independently
     */
    public function test_different_keys_processed_independently(): void
    {
        $key1 = 'test-key-1-' . now()->timestamp;
        $key2 = 'test-key-2-' . now()->timestamp;

        $response1 = $this->postJson('/api/test-idempotency', [
            'name' => 'Product 1',
        ], [
            'X-Idempotency-Key' => $key1,
        ]);

        $response2 = $this->postJson('/api/test-idempotency', [
            'name' => 'Product 2',
        ], [
            'X-Idempotency-Key' => $key2,
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Both should be stored as separate records
        $this->assertDatabaseHas('idempotency_keys', ['key' => $key1, 'status' => 'completed']);
        $this->assertDatabaseHas('idempotency_keys', ['key' => $key2, 'status' => 'completed']);
    }

    /**
     * Test: GET requests skip idempotency check
     */
    public function test_get_requests_skip_idempotency_check(): void
    {
        $response = $this->getJson('/api/test-idempotency');

        // Should not fail due to missing idempotency key
        $response->assertStatus(200);
    }

    /**
     * Test: Idempotency key from header takes precedence
     */
    public function test_header_key_takes_precedence(): void
    {
        $headerKey = 'header-key-' . now()->timestamp;

        $response = $this->postJson('/api/test-idempotency', [
            'name' => 'Test',
            'idempotency_key' => 'body-key', // This should be ignored
        ], [
            'X-Idempotency-Key' => $headerKey,
        ]);

        $response->assertStatus(200);

        // Should use header key, not body key
        $this->assertDatabaseHas('idempotency_keys', [
            'key' => $headerKey,
            'status' => 'completed',
        ]);

        $this->assertDatabaseMissing('idempotency_keys', [
            'key' => 'body-key',
        ]);
    }

    /**
     * Test: Cleanup command removes old keys
     */
    public function test_cleanup_removes_old_keys(): void
    {
        // Create an old key (31 days old)
        $oldKey = IdempotencyKey::create([
            'key' => 'old-key-' . now()->timestamp,
            'status' => 'completed',
            'created_at' => now()->subDays(31),
            'updated_at' => now()->subDays(31),
        ]);

        // Create a recent key (1 day old)
        $recentKey = IdempotencyKey::create([
            'key' => 'recent-key-' . now()->timestamp,
            'status' => 'completed',
            'created_at' => now()->subDays(1),
            'updated_at' => now()->subDays(1),
        ]);

        // Run cleanup (default: keep 30 days)
        $this->artisan('idempotency:prune --days=30');

        // Old key should be deleted
        $this->assertDatabaseMissing('idempotency_keys', [
            'key' => 'old-key-' . now()->timestamp,
        ]);

        // Recent key should remain
        $this->assertDatabaseHas('idempotency_keys', [
            'key' => 'recent-key-' . now()->timestamp,
        ]);
    }
}

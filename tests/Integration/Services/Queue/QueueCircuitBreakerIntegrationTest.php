<?php

namespace Tests\Integration\Services\Queue;

use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Monitoring\AlertService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

/**
 * QueueCircuitBreakerIntegrationTest
 * 
 * Tests d'intégration pour QueueCircuitBreaker avec Redis réel
 */
class QueueCircuitBreakerIntegrationTest extends IntegrationTestCase
{
    protected QueueCircuitBreaker $circuitBreaker;
    protected AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock external dependencies only
        Http::fake();
        Mail::fake();

        // Use real services
        $this->alertService = app(AlertService::class);
        $this->circuitBreaker = app(QueueCircuitBreaker::class);
    }
    #[Test]
    public function circuit_starts_in_closed_state(): void
    {
        // Act
        $isOpen = $this->circuitBreaker->isOpen('test-queue');

        // Assert
        $this->assertFalse($isOpen, 'Circuit should start in CLOSED state');
        $this->assertRedisKeyNotExists('circuit_breaker:test-queue:state');
    }
    #[Test]
    public function circuit_opens_after_threshold_failures(): void
    {
        // Arrange
        $queue = 'test-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');

        // Act - Record failures up to threshold
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        // Assert
        $this->assertTrue($this->circuitBreaker->isOpen($queue), 'Circuit should be OPEN after threshold failures');
        $this->assertRedisKeyEquals("circuit_breaker:{$queue}:state", 'open');
    }
    #[Test]
    public function circuit_transitions_to_half_open_after_timeout(): void
    {
        // Arrange
        $queue = 'test-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');
        $timeout = config('queue-protection.circuit_breaker.timeout');

        // Open circuit
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }
        $this->assertTrue($this->circuitBreaker->isOpen($queue));

        // Simulate timeout by setting opened_at in the past (ISO8601 format)
        $pastTime = \Carbon\Carbon::now()->subSeconds($timeout + 10);
        $this->setRedisValue("circuit_breaker:{$queue}:opened_at", $pastTime->toIso8601String(), 3600);

        // Act
        $isOpen = $this->circuitBreaker->isOpen($queue);

        // Assert - Should transition to HALF_OPEN and allow test
        $this->assertFalse($isOpen, 'Circuit should allow test after timeout (HALF_OPEN)');
        $this->assertRedisKeyEquals("circuit_breaker:{$queue}:state", 'half_open');
    }
    #[Test]
    public function circuit_closes_after_success_threshold_in_half_open(): void
    {
        // Arrange
        $queue = 'test-queue';
        $successThreshold = config('queue-protection.circuit_breaker.success_threshold');

        // Set circuit to HALF_OPEN manually
        $this->setRedisValue("circuit_breaker:{$queue}:state", 'half_open', 3600);

        // Act - Record successes up to threshold
        for ($i = 0; $i < $successThreshold; $i++) {
            $this->circuitBreaker->recordSuccess($queue);
        }

        // Assert - Circuit should be CLOSED
        $this->assertFalse($this->circuitBreaker->isOpen($queue), 'Circuit should be CLOSED after success threshold');
        $this->assertRedisKeyEquals("circuit_breaker:{$queue}:state", 'closed');
    }
    #[Test]
    public function circuit_reopens_on_failure_in_half_open(): void
    {
        // Arrange
        $queue = 'test-queue';

        // Set circuit to HALF_OPEN
        $this->setRedisValue("circuit_breaker:{$queue}:state", 'half_open', 3600);

        // Act - Record failure
        $this->circuitBreaker->recordFailure($queue);

        // Assert - Circuit should be OPEN again
        $this->assertTrue($this->circuitBreaker->isOpen($queue), 'Circuit should reopen on failure in HALF_OPEN');
        $this->assertRedisKeyEquals("circuit_breaker:{$queue}:state", 'open');
    }
    #[Test]
    public function reset_clears_state_and_closes_circuit(): void
    {
        // Arrange
        $queue = 'test-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');

        // Open circuit
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }
        $this->assertTrue($this->circuitBreaker->isOpen($queue));

        // Act
        $this->circuitBreaker->reset($queue);

        // Assert - Circuit should be CLOSED with state='closed'
        $this->assertFalse($this->circuitBreaker->isOpen($queue), 'Circuit should be CLOSED after reset');
        $this->assertRedisKeyEquals("circuit_breaker:{$queue}:state", 'closed');
        $this->assertRedisKeyNotExists("circuit_breaker:{$queue}:opened_at");
    }
    #[Test]
    public function multiple_queues_have_independent_states(): void
    {
        // Arrange
        $queue1 = 'queue-1';
        $queue2 = 'queue-2';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');

        // Act - Open only queue1
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue1);
        }

        // Assert
        $this->assertTrue($this->circuitBreaker->isOpen($queue1), 'Queue 1 should be OPEN');
        $this->assertFalse($this->circuitBreaker->isOpen($queue2), 'Queue 2 should be CLOSED');
    }
    #[Test]
    public function state_persists_in_redis(): void
    {
        // Arrange
        $queue = 'persistent-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');

        // Act - Open circuit
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        // Create new instance to test persistence
        $newCircuitBreaker = new QueueCircuitBreaker();

        // Assert - State should persist
        $this->assertTrue($newCircuitBreaker->isOpen($queue), 'State should persist across instances');
    }
    #[Test]
    public function state_expires_after_ttl(): void
    {
        // Arrange
        $queue = 'expiring-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');
        $ttl = config('queue-protection.circuit_breaker.ttl');

        // Act - Open circuit
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        // Assert - TTL should be set
        $stateTtl = $this->getRedisTtl("circuit_breaker:{$queue}:state");
        $this->assertGreaterThan(0, $stateTtl, 'State should have TTL');
        $this->assertLessThanOrEqual($ttl, $stateTtl, 'TTL should not exceed configured value');
    }
    #[Test]
    public function alert_sent_when_circuit_opens(): void
    {
        // Arrange
        Log::spy();
        $queue = 'alert-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');

        // Act - Open circuit
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        // Assert - Alert should be logged (critical level)
        // Service logs twice: once via AlertService, once as fallback
        Log::shouldHaveReceived('critical')
            ->atLeast()->once()
            ->with(\Mockery::pattern('/Circuit.*opened|ALERT.*Circuit/i'), \Mockery::any());
    }
}

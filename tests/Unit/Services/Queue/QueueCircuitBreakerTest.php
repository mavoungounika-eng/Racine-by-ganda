<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Monitoring\AlertService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class QueueCircuitBreakerTest extends TestCase
{
    protected QueueCircuitBreaker $circuitBreaker;
    protected $redisMock;
    protected $alertServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Redis
        $this->redisMock = Mockery::mock('alias:' . Redis::class);
        
        // Mock AlertService
        $this->alertServiceMock = Mockery::mock(AlertService::class);
        $this->app->instance(AlertService::class, $this->alertServiceMock);

        // Create instance
        $this->circuitBreaker = new QueueCircuitBreaker();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow(); // Reset time
        parent::tearDown();
    }
    #[Test]
    public function circuit_starts_in_closed_state()
    {
        // Arrange
        $this->redisMock->shouldReceive('get')
            ->with('circuit_breaker:default:state')
            ->andReturn(null);

        // Act
        $isOpen = $this->circuitBreaker->isOpen('default');

        // Assert
        $this->assertFalse($isOpen, 'Circuit should start in CLOSED state');
    }
    #[Test]
    public function circuit_opens_after_threshold_failures()
    {
        // Arrange
        $queue = 'default';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold', 5);

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('closed');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn($threshold - 1);

        $this->redisMock->shouldReceive('incr')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn($threshold);

        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:state",
                Mockery::any(),
                'open'
            )
            ->andReturn(true);

        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:opened_at",
                Mockery::any(),
                Mockery::type('int')
            )
            ->andReturn(true);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn(1);

        // Mock alert
        $this->alertServiceMock->shouldReceive('critical')
            ->once()
            ->with(
                Mockery::pattern('/Circuit Breaker Opened/'),
                Mockery::any(),
                Mockery::type('array')
            );

        // Act
        $this->circuitBreaker->recordFailure($queue);

        // Assert - Next call should show circuit is open
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('open');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:opened_at")
            ->andReturn(time());

        $isOpen = $this->circuitBreaker->isOpen($queue);
        $this->assertTrue($isOpen, 'Circuit should be OPEN after threshold failures');
    }
    #[Test]
    public function circuit_transitions_to_half_open_after_timeout()
    {
        // Arrange
        $queue = 'default';
        $timeout = config('queue-protection.circuit_breaker.timeout', 60);
        $openedAt = time() - ($timeout + 10); // Opened more than timeout ago

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('open');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:opened_at")
            ->andReturn($openedAt);

        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:state",
                Mockery::any(),
                'half_open'
            )
            ->andReturn(true);

        // Act
        $isOpen = $this->circuitBreaker->isOpen($queue);

        // Assert
        $this->assertFalse($isOpen, 'Circuit should transition to HALF_OPEN after timeout');
    }
    #[Test]
    public function circuit_closes_after_success_threshold_in_half_open()
    {
        // Arrange
        $queue = 'default';
        $threshold = config('queue-protection.circuit_breaker.success_threshold', 2);

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('half_open');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:success_count")
            ->andReturn($threshold - 1);

        $this->redisMock->shouldReceive('incr')
            ->with("circuit_breaker:{$queue}:success_count")
            ->andReturn($threshold);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn(1);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:success_count")
            ->andReturn(1);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:opened_at")
            ->andReturn(1);

        // Act
        $this->circuitBreaker->recordSuccess($queue);

        // Assert - Next call should show circuit is closed
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn(null);

        $isOpen = $this->circuitBreaker->isOpen($queue);
        $this->assertFalse($isOpen, 'Circuit should be CLOSED after success threshold in HALF_OPEN');
    }
    #[Test]
    public function circuit_reopens_on_failure_in_half_open()
    {
        // Arrange
        $queue = 'default';

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('half_open');

        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:state",
                Mockery::any(),
                'open'
            )
            ->andReturn(true);

        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:opened_at",
                Mockery::any(),
                Mockery::type('int')
            )
            ->andReturn(true);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:success_count")
            ->andReturn(1);

        // Mock alert
        $this->alertServiceMock->shouldReceive('critical')
            ->once();

        // Act
        $this->circuitBreaker->recordFailure($queue);

        // Assert - Circuit should be open again
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('open');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:opened_at")
            ->andReturn(time());

        $isOpen = $this->circuitBreaker->isOpen($queue);
        $this->assertTrue($isOpen, 'Circuit should reopen on failure in HALF_OPEN');
    }
    #[Test]
    public function record_failure_increments_counter()
    {
        // Arrange
        $queue = 'default';

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('closed');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn(2);

        $this->redisMock->shouldReceive('incr')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->once()
            ->andReturn(3);

        // Act
        $this->circuitBreaker->recordFailure($queue);

        // Assert - Mockery will verify incr was called
        $this->assertTrue(true);
    }
    #[Test]
    public function record_success_resets_failure_counter()
    {
        // Arrange
        $queue = 'default';

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('closed');

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->once()
            ->andReturn(1);

        // Act
        $this->circuitBreaker->recordSuccess($queue);

        // Assert - Mockery will verify del was called
        $this->assertTrue(true);
    }
    #[Test]
    public function consecutive_failures_trigger_open_state()
    {
        // Arrange
        $queue = 'test-queue';
        $threshold = 3; // Lower threshold for testing

        // Override config for this test
        config(['queue-protection.circuit_breaker.failure_threshold' => $threshold]);

        // Simulate consecutive failures
        for ($i = 1; $i < $threshold; $i++) {
            $this->redisMock->shouldReceive('get')
                ->with("circuit_breaker:{$queue}:state")
                ->andReturn('closed');

            $this->redisMock->shouldReceive('get')
                ->with("circuit_breaker:{$queue}:failure_count")
                ->andReturn($i - 1);

            $this->redisMock->shouldReceive('incr')
                ->with("circuit_breaker:{$queue}:failure_count")
                ->andReturn($i);

            $this->circuitBreaker->recordFailure($queue);
        }

        // Final failure should open circuit
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('closed');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn($threshold - 1);

        $this->redisMock->shouldReceive('incr')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn($threshold);

        $this->redisMock->shouldReceive('setex')->andReturn(true);
        $this->redisMock->shouldReceive('del')->andReturn(1);

        $this->alertServiceMock->shouldReceive('critical')->once();

        // Act
        $this->circuitBreaker->recordFailure($queue);

        // Assert
        $this->assertTrue(true, 'Consecutive failures should trigger OPEN state');
    }
    #[Test]
    public function state_persists_in_redis()
    {
        // Arrange
        $queue = 'persistent-queue';

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('open');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:opened_at")
            ->andReturn(time());

        // Act
        $isOpen = $this->circuitBreaker->isOpen($queue);

        // Assert
        $this->assertTrue($isOpen, 'State should persist in Redis');
    }
    #[Test]
    public function state_expires_after_ttl()
    {
        // Arrange
        $queue = 'expiring-queue';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        // Expect setex with TTL
        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:state",
                $ttl,
                Mockery::any()
            )
            ->once()
            ->andReturn(true);

        $this->redisMock->shouldReceive('setex')
            ->with(
                "circuit_breaker:{$queue}:opened_at",
                $ttl,
                Mockery::any()
            )
            ->andReturn(true);

        $this->redisMock->shouldReceive('get')->andReturn('closed');
        $this->redisMock->shouldReceive('get')->andReturn(4);
        $this->redisMock->shouldReceive('incr')->andReturn(5);
        $this->redisMock->shouldReceive('del')->andReturn(1);

        $this->alertServiceMock->shouldReceive('critical')->once();

        // Act
        $this->circuitBreaker->recordFailure($queue);

        // Assert - Mockery will verify setex was called with correct TTL
        $this->assertTrue(true);
    }
    #[Test]
    public function multiple_queues_have_independent_states()
    {
        // Arrange
        $queue1 = 'queue-1';
        $queue2 = 'queue-2';

        // Queue 1 is open
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue1}:state")
            ->andReturn('open');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue1}:opened_at")
            ->andReturn(time());

        // Queue 2 is closed
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue2}:state")
            ->andReturn(null);

        // Act
        $queue1IsOpen = $this->circuitBreaker->isOpen($queue1);
        $queue2IsOpen = $this->circuitBreaker->isOpen($queue2);

        // Assert
        $this->assertTrue($queue1IsOpen, 'Queue 1 should be OPEN');
        $this->assertFalse($queue2IsOpen, 'Queue 2 should be CLOSED');
    }
    #[Test]
    public function reset_clears_state_and_closes_circuit()
    {
        // Arrange
        $queue = 'reset-queue';

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:state")
            ->once()
            ->andReturn(1);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->once()
            ->andReturn(1);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:success_count")
            ->once()
            ->andReturn(1);

        $this->redisMock->shouldReceive('del')
            ->with("circuit_breaker:{$queue}:opened_at")
            ->once()
            ->andReturn(1);

        // Act
        $this->circuitBreaker->reset($queue);

        // Assert - Verify circuit is closed after reset
        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn(null);

        $isOpen = $this->circuitBreaker->isOpen($queue);
        $this->assertFalse($isOpen, 'Circuit should be CLOSED after reset');
    }
    #[Test]
    public function alert_sent_when_circuit_opens()
    {
        // Arrange
        $queue = 'alert-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold', 5);

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:state")
            ->andReturn('closed');

        $this->redisMock->shouldReceive('get')
            ->with("circuit_breaker:{$queue}:failure_count")
            ->andReturn($threshold - 1);

        $this->redisMock->shouldReceive('incr')->andReturn($threshold);
        $this->redisMock->shouldReceive('setex')->andReturn(true);
        $this->redisMock->shouldReceive('del')->andReturn(1);

        // Expect alert to be sent
        $this->alertServiceMock->shouldReceive('critical')
            ->once()
            ->with(
                Mockery::pattern('/Circuit Breaker Opened/'),
                Mockery::any(),
                Mockery::on(function ($context) use ($queue, $threshold) {
                    return $context['queue'] === $queue
                        && $context['failure_count'] === $threshold;
                })
            );

        // Act
        $this->circuitBreaker->recordFailure($queue);

        // Assert - Mockery will verify alert was sent
        $this->assertTrue(true);
    }
}

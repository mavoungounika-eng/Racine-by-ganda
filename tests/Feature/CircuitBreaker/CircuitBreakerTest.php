<?php

namespace Tests\Feature\CircuitBreaker;

use App\Models\CircuitBreaker;
use App\Services\Webhooks\CircuitBreakerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    use RefreshDatabase;

    private CircuitBreakerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CircuitBreakerService::class);
    }

    // ============================================================================
    // INITIALIZATION & STATE TRACKING
    // ============================================================================

    /** @test */
    public function circuit_breaker_initializes_in_closed_state(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        
        $this->assertTrue($breaker->isClosed());
        $this->assertFalse($breaker->isOpen());
        $this->assertFalse($breaker->isHalfOpen());
        $this->assertEquals(0, $breaker->failure_count);
    }

    /** @test */
    public function is_available_returns_true_when_closed(): void
    {
        $this->assertTrue($this->service->isAvailable('stripe'));
    }

    // ============================================================================
    // FAILURE TRACKING & OPENING CIRCUIT
    // ============================================================================

    /** @test */
    public function records_failures_incrementally(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        
        $breaker->recordFailure('Network error');
        $this->assertEquals(1, $breaker->failure_count);
        
        $breaker->recordFailure('Timeout');
        $this->assertEquals(2, $breaker->failure_count);
        $this->assertEquals('Timeout', $breaker->last_error);
    }

    /** @test */
    public function opens_circuit_when_failure_threshold_exceeded(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->max_failures = 3;
        $breaker->save();
        
        $breaker->recordFailure('Error 1');
        $breaker->recordFailure('Error 2');
        $this->assertTrue($breaker->isClosed());
        
        // Third failure should trigger open
        $this->service->recordFailure('stripe', 'Error 3');
        $breaker = $breaker->fresh();
        
        $this->assertTrue($breaker->isOpen());
        $this->assertNotNull($breaker->opened_at);
    }

    /** @test */
    public function circuit_rejects_requests_when_open(): void
    {
        $breaker = CircuitBreaker::forProvider('monetbil');
        $breaker->update(['state' => 'open', 'opened_at' => now()]);
        
        $this->assertFalse($this->service->isAvailable('monetbil'));
    }

    // ============================================================================
    // RECOVERY: HALF_OPEN STATE
    // ============================================================================

    /** @test */
    public function transitions_to_half_open_after_delay(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update([
            'state' => 'open',
            'opened_at' => now()->subSeconds(61), // 61 seconds ago
        ]);
        
        // Should transition to HALF_OPEN and allow request
        $this->assertTrue($this->service->isAvailable('stripe'));
        $breaker = $breaker->fresh();
        $this->assertTrue($breaker->isHalfOpen());
    }

    /** @test */
    public function half_open_allows_recovery_test(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update(['state' => 'half_open']);
        
        $this->assertTrue($this->service->isAvailable('stripe'));
    }

    /** @test */
    public function closes_circuit_after_success_in_half_open(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update(['state' => 'half_open']);
        
        $this->service->recordSuccess('stripe');
        $breaker = $breaker->fresh();
        
        $this->assertTrue($breaker->isClosed());
        $this->assertEquals(0, $breaker->failure_count);
        $this->assertEquals(0, $breaker->success_count);
        $this->assertNull($breaker->opened_at);
    }

    /** @test */
    public function reopens_circuit_after_failure_in_half_open(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update(['state' => 'half_open']);
        
        $this->service->recordFailure('stripe', 'Still failing');
        $breaker = $breaker->fresh();
        
        $this->assertTrue($breaker->isOpen());
        $this->assertEquals('Still failing', $breaker->last_error);
    }

    // ============================================================================
    // SUCCESS TRACKING
    // ============================================================================

    /** @test */
    public function records_successful_operations(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        
        $breaker->recordSuccess();
        $this->assertEquals(1, $breaker->success_count);
        
        $breaker->recordSuccess();
        $this->assertEquals(2, $breaker->success_count);
    }

    // ============================================================================
    // THRESHOLD & BACKOFF CALCULATIONS
    // ============================================================================

    /** @test */
    public function checks_if_exceeded_failure_threshold(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update(['failure_count' => 3, 'max_failures' => 5]);
        
        $this->assertFalse($breaker->hasExceededFailureThreshold());
        
        $breaker->update(['failure_count' => 5]);
        $this->assertTrue($breaker->hasExceededFailureThreshold());
    }

    /** @test */
    public function calculates_exponential_backoff_delay(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        
        // Backoff: 10s (1 fail) → 20s (2 fail) → 40s (3 fail) → 80s (4 fail)
        $breaker->update(['failure_count' => 1]);
        $this->assertEquals(10, $breaker->getBackoffDelaySeconds());
        
        $breaker->update(['failure_count' => 2]);
        $this->assertEquals(20, $breaker->getBackoffDelaySeconds());
        
        $breaker->update(['failure_count' => 3]);
        $this->assertEquals(40, $breaker->getBackoffDelaySeconds());
        
        $breaker->update(['failure_count' => 4]);
        $this->assertEquals(80, $breaker->getBackoffDelaySeconds());
    }

    /** @test */
    public function checks_readiness_for_half_open_transition(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        
        // Recent open - not ready
        $breaker->update(['state' => 'open', 'opened_at' => now()->subSeconds(30)]);
        $this->assertFalse($breaker->isReadyForHalfOpen(60));
        
        // After delay - ready
        $breaker->update(['opened_at' => now()->subSeconds(61)]);
        $this->assertTrue($breaker->isReadyForHalfOpen(60));
    }

    // ============================================================================
    // STATUS & STATISTICS
    // ============================================================================

    /** @test */
    public function returns_status_summary(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update([
            'state' => 'open',
            'failure_count' => 5,
            'success_count' => 2,
            'last_error' => 'Network timeout',
            'opened_at' => now(),
        ]);
        
        $status = $this->service->getStatus('stripe');
        
        $this->assertEquals('stripe', $status['provider']);
        $this->assertEquals('open', $status['state']);
        $this->assertEquals(5, $status['failures']);
        $this->assertEquals(2, $status['successes']);
        $this->assertNotNull($status['open_since']);
    }

    /** @test */
    public function returns_all_statistics(): void
    {
        CircuitBreaker::forProvider('stripe')->update(['state' => 'closed']);
        CircuitBreaker::forProvider('monetbil')->update(['state' => 'open', 'opened_at' => now()]);
        CircuitBreaker::forProvider('paypal')->update(['state' => 'half_open']);
        
        $stats = $this->service->getAllStatistics();
        
        $this->assertEquals(3, $stats['total_circuits']);
        $this->assertEquals(1, $stats['closed']);
        $this->assertEquals(1, $stats['open']);
        $this->assertEquals(1, $stats['half_open']);
        $this->assertCount(3, $stats['breakers']);
    }

    // ============================================================================
    // MANUAL OPERATIONS (ADMIN)
    // ============================================================================

    /** @test */
    public function manually_resets_circuit_breaker(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update([
            'state' => 'open',
            'failure_count' => 5,
            'opened_at' => now(),
        ]);
        
        $this->service->reset('stripe');
        $breaker = $breaker->fresh();
        
        $this->assertTrue($breaker->isClosed());
        $this->assertEquals(0, $breaker->failure_count);
        $this->assertNull($breaker->opened_at);
    }

    /** @test */
    public function resets_all_circuit_breakers(): void
    {
        CircuitBreaker::forProvider('stripe')->update(['state' => 'open', 'failure_count' => 5]);
        CircuitBreaker::forProvider('monetbil')->update(['state' => 'open', 'failure_count' => 3]);
        
        $this->service->resetAll();
        
        $this->assertTrue(CircuitBreaker::where('provider', 'stripe')->first()->isClosed());
        $this->assertTrue(CircuitBreaker::where('provider', 'monetbil')->first()->isClosed());
    }

    // ============================================================================
    // INTEGRATION: SERVICE METHODS
    // ============================================================================

    /** @test */
    public function service_tracks_success_state_transitions(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->max_failures = 2;
        $breaker->save();
        
        // Trigger failures
        $this->service->recordFailure('stripe', 'Error 1');
        $this->service->recordFailure('stripe', 'Error 2');
        
        // Circuit should now be open
        $breaker = $breaker->fresh();
        $this->assertTrue($breaker->isOpen());
        
        // Wait for recovery window (in test, simulate)
        $breaker->update(['opened_at' => now()->subSeconds(61)]);
        
        // Request allowed in HALF_OPEN
        $this->assertTrue($this->service->isAvailable('stripe'));
        
        // Success closes circuit
        $this->service->recordSuccess('stripe');
        $breaker = $breaker->fresh();
        $this->assertTrue($breaker->isClosed());
    }

    /** @test */
    public function different_providers_have_independent_circuits(): void
    {
        $stripe = CircuitBreaker::forProvider('stripe');
        $monetbil = CircuitBreaker::forProvider('monetbil');
        
        $stripe->update(['state' => 'open', 'opened_at' => now()]);
        
        $this->assertFalse($this->service->isAvailable('stripe'));
        $this->assertTrue($this->service->isAvailable('monetbil'));
    }

    /** @test */
    public function updates_last_error_on_failure(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        
        $this->service->recordFailure('stripe', 'Connection refused');
        $breaker = $breaker->fresh();
        $this->assertEquals('Connection refused', $breaker->last_error);
        
        $this->service->recordFailure('stripe', 'Timeout after 30s');
        $breaker = $breaker->fresh();
        $this->assertEquals('Timeout after 30s', $breaker->last_error);
    }

    /** @test */
    public function returns_backoff_delay(): void
    {
        $breaker = CircuitBreaker::forProvider('stripe');
        $breaker->update(['failure_count' => 3]);
        
        $delay = $this->service->getBackoffDelay('stripe');
        $this->assertEquals(40, $delay); // 10 * 2^2 = 40 seconds
    }
}

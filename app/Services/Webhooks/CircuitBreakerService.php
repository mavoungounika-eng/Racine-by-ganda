<?php

namespace App\Services\Webhooks;

use App\Models\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker Pattern Implementation
 * 
 * Prevents cascade failures by stopping requests when a provider is consistently failing.
 * 
 * States:
 * - CLOSED: Normal operation, forward all requests
 * - OPEN: Too many failures, reject requests (return default response)
 * - HALF_OPEN: Test recovery, allow one request through
 * 
 * Transition Logic:
 * CLOSED --[failures >= threshold]--> OPEN
 * OPEN --[delay elapsed]--> HALF_OPEN
 * HALF_OPEN --[success]--> CLOSED
 * HALF_OPEN --[failure]--> OPEN
 */
class CircuitBreakerService
{
    private const CACHE_PREFIX = 'circuit_breaker_';
    private const DEFAULT_HALF_OPEN_DELAY = 60; // seconds
    private const DEFAULT_MAX_FAILURES = 5;

    /**
     * Check if circuit is available for requests.
     * 
     * Returns true if:
     * - Circuit is CLOSED (normal)
     * - Circuit is HALF_OPEN (testing recovery)
     * 
     * Returns false if:
     * - Circuit is OPEN (reject request)
     */
    public function isAvailable(string $provider): bool
    {
        $breaker = CircuitBreaker::forProvider($provider);
        $breaker = $breaker->fresh(); // Ensure fresh data

        if ($breaker->isClosed()) {
            return true;
        }

        if ($breaker->isHalfOpen()) {
            return true; // Allow test request
        }

        // Check if enough time has passed to transition from OPEN to HALF_OPEN
        if ($breaker->isOpen() && $breaker->isReadyForHalfOpen(self::DEFAULT_HALF_OPEN_DELAY)) {
            $breaker->halfOpen();
            Log::info("Circuit Breaker: {$provider} transitioned OPEN -> HALF_OPEN");
            return true; // Allow recovery test
        }

        // Circuit is OPEN and not ready for recovery
        Log::warning("Circuit Breaker: {$provider} is OPEN, rejecting request");
        return false;
    }

    /**
     * Record a successful webhook processing.
     */
    public function recordSuccess(string $provider): void
    {
        $breaker = CircuitBreaker::forProvider($provider);
        $breaker->recordSuccess();

        // If in HALF_OPEN state, close the circuit (recovery successful)
        if ($breaker->isHalfOpen()) {
            $breaker->close();
            Cache::forget($this->getCacheKey($provider));
            Log::info("Circuit Breaker: {$provider} recovered, state HALF_OPEN -> CLOSED");
        }
    }

    /**
     * Record a failed webhook processing.
     */
    public function recordFailure(string $provider, string $error = null): void
    {
        $breaker = CircuitBreaker::forProvider($provider);
        $breaker->recordFailure($error);

        // Check if we've exceeded the failure threshold
        if ($breaker->hasExceededFailureThreshold() && $breaker->isClosed()) {
            $breaker->open($error);
            Cache::put(
                $this->getCacheKey($provider),
                true,
                now()->addSeconds($breaker->getBackoffDelaySeconds())
            );
            Log::error("Circuit Breaker: {$provider} opened due to {$breaker->failure_count} failures");
        }

        // If in HALF_OPEN state, reopen immediately
        if ($breaker->isHalfOpen()) {
            $breaker->open($error);
            Cache::put(
                $this->getCacheKey($provider),
                true,
                now()->addSeconds($breaker->getBackoffDelaySeconds())
            );
            Log::error("Circuit Breaker: {$provider} reopened after failure in HALF_OPEN state");
        }
    }

    /**
     * Get circuit breaker status for a provider.
     */
    public function getStatus(string $provider): array
    {
        $breaker = CircuitBreaker::forProvider($provider);
        return $breaker->getStatusSummary();
    }

    /**
     * Get statistics for all circuit breakers.
     */
    public function getAllStatistics(): array
    {
        $breakers = CircuitBreaker::all();
        
        return [
            'total_circuits' => $breakers->count(),
            'closed' => $breakers->where('state', 'closed')->count(),
            'open' => $breakers->where('state', 'open')->count(),
            'half_open' => $breakers->where('state', 'half_open')->count(),
            'breakers' => $breakers->map(fn ($b) => $b->getStatusSummary())->all(),
        ];
    }

    /**
     * Manually reset a circuit breaker (admin operation).
     */
    public function reset(string $provider): void
    {
        $breaker = CircuitBreaker::forProvider($provider);
        $breaker->close();
        Cache::forget($this->getCacheKey($provider));
        Log::info("Circuit Breaker: {$provider} manually reset");
    }

    /**
     * Reset all circuit breakers.
     */
    public function resetAll(): void
    {
        CircuitBreaker::all()->each(fn ($breaker) => $this->reset($breaker->provider));
    }

    /**
     * Get backoff delay for a provider (in seconds).
     */
    public function getBackoffDelay(string $provider): int
    {
        $breaker = CircuitBreaker::forProvider($provider);
        return $breaker->getBackoffDelaySeconds();
    }

    /**
     * Generate cache key for circuit breaker.
     */
    private function getCacheKey(string $provider): string
    {
        return self::CACHE_PREFIX . $provider;
    }
}

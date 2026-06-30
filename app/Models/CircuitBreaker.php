<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CircuitBreaker extends Model
{
    protected $fillable = [
        'provider',
        'state',
        'failure_count',
        'success_count',
        'max_failures',
        'opened_at',
        'half_opened_at',
        'closed_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'opened_at' => 'datetime',
        'half_opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Get circuit breaker by provider.
     */
    public static function forProvider(string $provider): self
    {
        return self::firstOrCreate(
            ['provider' => $provider],
            ['state' => 'closed']
        );
    }

    /**
     * Check if circuit is CLOSED (normal operation).
     */
    public function isClosed(): bool
    {
        return $this->state === 'closed';
    }

    /**
     * Check if circuit is OPEN (failing, reject requests).
     */
    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    /**
     * Check if circuit is HALF_OPEN (testing recovery).
     */
    public function isHalfOpen(): bool
    {
        return $this->state === 'half_open';
    }

    /**
     * Open the circuit (too many failures).
     */
    public function open(string $error = null): self
    {
        $this->update([
            'state' => 'open',
            'opened_at' => now(),
            'last_error' => $error,
        ]);

        return $this;
    }

    /**
     * Transition to HALF_OPEN (test recovery after delay).
     */
    public function halfOpen(): self
    {
        $this->update([
            'state' => 'half_open',
            'half_opened_at' => now(),
        ]);

        return $this;
    }

    /**
     * Close the circuit (recovery successful).
     */
    public function close(): self
    {
        $this->update([
            'state' => 'closed',
            'closed_at' => now(),
            'opened_at' => null,  // Clear opened_at when closing
            'failure_count' => 0,
            'success_count' => 0,
        ]);

        return $this;
    }

    /**
     * Record a failure attempt.
     */
    public function recordFailure(string $error = null): self
    {
        $this->increment('failure_count');
        
        if ($error) {
            $this->update(['last_error' => $error]);
        }

        return $this;
    }

    /**
     * Record a success attempt.
     */
    public function recordSuccess(): self
    {
        $this->increment('success_count');

        return $this;
    }

    /**
     * Check if max failures threshold reached.
     */
    public function hasExceededFailureThreshold(): bool
    {
        return $this->failure_count >= $this->max_failures;
    }

    /**
     * Check if circuit should transition from OPEN to HALF_OPEN.
     * Default: 60 seconds after opening.
     */
    public function isReadyForHalfOpen(int $secondsSinceOpen = 60): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        if (!$this->opened_at) {
            return false;
        }

        // Use Carbon's diffInSeconds which is more reliable
        return $this->opened_at->diffInSeconds(now()) >= $secondsSinceOpen;
    }

    /**
     * Calculate backoff delay in seconds based on failure count.
     * Exponential: 10s, 20s, 40s, 80s, etc.
     */
    public function getBackoffDelaySeconds(): int
    {
        $baseDelay = 10;
        $multiplier = pow(2, min($this->failure_count - 1, 5)); // Cap at 2^5 = 32x
        
        return (int) ($baseDelay * $multiplier);
    }

    /**
     * Get human-readable status summary.
     */
    public function getStatusSummary(): array
    {
        return [
            'provider' => $this->provider,
            'state' => $this->state,
            'failures' => $this->failure_count,
            'successes' => $this->success_count,
            'threshold' => $this->max_failures,
            'open_since' => $this->opened_at?->diffForHumans(),
            'backoff_delay' => $this->state === 'open' ? $this->getBackoffDelaySeconds() . 's' : null,
            'last_error' => $this->last_error,
        ];
    }
}

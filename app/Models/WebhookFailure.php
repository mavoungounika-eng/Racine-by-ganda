<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookFailure extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'external_id',
        'payload',
        'error_message',
        'retry_count',
        'last_retry_at',
        'status',
        'signature',
        'metadata',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'last_retry_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope: Get pending failures
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get failures by provider
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: Get failures that need retry
     */
    public function scopeNeedingRetry($query)
    {
        return $query->whereIn('status', ['pending', 'failed'])
                    ->where('retry_count', '<', 3)
                    ->where(function ($q) {
                        // No retry yet, or enough time has passed
                        $q->whereNull('last_retry_at')
                          ->orWhere('last_retry_at', '<=', now()->subMinutes(5));
                    });
    }

    /**
     * Scope: Get dead letter failures
     */
    public function scopeDeadLetter($query)
    {
        return $query->where('status', 'dead_letter');
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status' => 'processed',
            'retry_count' => 0,
        ]);
    }

    /**
     * Mark as failed and increment retry count
     */
    public function markAsFailed(string $errorMessage): void
    {
        $retryCount = $this->retry_count + 1;
        $status = $retryCount >= 3 ? 'dead_letter' : 'failed';

        $this->update([
            'status' => $status,
            'error_message' => $errorMessage,
            'retry_count' => $retryCount,
            'last_retry_at' => now(),
        ]);
    }

    /**
     * Check if failure can be retried
     */
    public function canRetry(): bool
    {
        if ($this->status === 'dead_letter') {
            return false;
        }

        if ($this->retry_count >= 3) {
            return false;
        }

        // Check if enough time has passed since last retry
        if ($this->last_retry_at && $this->last_retry_at->addMinutes(5)->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted event description
     */
    public function getEventDescription(): string
    {
        return sprintf(
            '%s: %s (retry #%d)',
            strtoupper($this->provider),
            $this->event_type,
            $this->retry_count
        );
    }
}

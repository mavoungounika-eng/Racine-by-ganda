<?php

namespace App\Services\Webhooks;

use App\Models\WebhookFailure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WebhookDeduplicationService
{
    /**
     * Cache key prefix for webhook deduplication (5 minutes TTL)
     */
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_PREFIX = 'webhook_dedupe:';

    /**
     * Check if webhook has been seen before (deduplication)
     * 
     * @param string $provider Provider name (stripe, monetbil, etc.)
     * @param string $externalId External ID from provider (event_id, event_key, etc.)
     * @return bool True if webhook was already processed, false if new
     */
    public function isDuplicate(string $provider, string $externalId): bool
    {
        $cacheKey = self::CACHE_PREFIX . $provider . ':' . $externalId;
        
        if (Cache::has($cacheKey)) {
            Log::info('WebhookDeduplicationService: Duplicate webhook detected', [
                'provider' => $provider,
                'external_id' => $externalId,
            ]);
            return true;
        }

        // Mark as seen
        Cache::put($cacheKey, true, self::CACHE_TTL);
        return false;
    }

    /**
     * Record a webhook that failed
     * 
     * @param string $provider Provider name
     * @param string $eventType Event type (charge.refunded, success, etc.)
     * @param string $externalId External ID for deduplication
     * @param array $payload Full webhook payload
     * @param string $signature Webhook signature for verification
     * @param ?string $errorMessage Error message if processing failed
     * @return WebhookFailure
     */
    public function recordFailure(
        string $provider,
        string $eventType,
        string $externalId,
        array $payload,
        string $signature = '',
        ?string $errorMessage = null
    ): WebhookFailure {
        try {
            $failure = WebhookFailure::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'provider' => $provider,
                    'event_type' => $eventType,
                    'payload' => $payload,
                    'signature' => $signature,
                    'error_message' => $errorMessage,
                    'status' => $errorMessage ? 'failed' : 'pending',
                    'retry_count' => $errorMessage ? 1 : 0,
                ]
            );

            Log::warning('WebhookDeduplicationService: Webhook failure recorded', [
                'webhook_failure_id' => $failure->id,
                'provider' => $provider,
                'external_id' => $externalId,
                'event_type' => $eventType,
                'error' => $errorMessage,
            ]);

            return $failure;
        } catch (\Exception $e) {
            Log::error('WebhookDeduplicationService: Failed to record webhook failure', [
                'provider' => $provider,
                'external_id' => $externalId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mark a webhook failure as successfully processed
     */
    public function markAsProcessed(WebhookFailure $failure): void
    {
        $failure->markAsProcessed();
        
        Log::info('WebhookDeduplicationService: Webhook marked as processed', [
            'webhook_failure_id' => $failure->id,
            'provider' => $failure->provider,
            'external_id' => $failure->external_id,
        ]);
    }

    /**
     * Mark a webhook failure for retry
     */
    public function markForRetry(WebhookFailure $failure, string $errorMessage): void
    {
        $failure->markAsFailed($errorMessage);
        
        Log::warning('WebhookDeduplicationService: Webhook marked for retry', [
            'webhook_failure_id' => $failure->id,
            'provider' => $failure->provider,
            'external_id' => $failure->external_id,
            'retry_count' => $failure->retry_count,
            'status' => $failure->status,
        ]);
    }

    /**
     * Get all pending webhook failures that need retry
     */
    public function getPendingFailures(int $limit = 50)
    {
        return WebhookFailure::needingRetry()
            ->orderBy('retry_count', 'asc')
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get dead letter (permanently failed) webhooks
     */
    public function getDeadLetterFailures(int $limit = 50)
    {
        return WebhookFailure::deadLetter()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get webhook failure statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => WebhookFailure::count(),
            'pending' => WebhookFailure::pending()->count(),
            'dead_letter' => WebhookFailure::deadLetter()->count(),
            'by_provider' => WebhookFailure::select('provider')
                ->selectRaw('count(*) as count')
                ->groupBy('provider')
                ->get()
                ->pluck('count', 'provider')
                ->toArray(),
            'retry_distribution' => WebhookFailure::select('retry_count')
                ->selectRaw('count(*) as count')
                ->groupBy('retry_count')
                ->get()
                ->pluck('count', 'retry_count')
                ->toArray(),
        ];
    }

    /**
     * Retry a failed webhook
     * 
     * @param WebhookFailure $failure
     * @param callable $handler Callback to execute for retry
     * @return bool True if successful, false otherwise
     */
    public function retry(WebhookFailure $failure, callable $handler): bool
    {
        if (!$failure->canRetry()) {
            Log::warning('WebhookDeduplicationService: Cannot retry webhook', [
                'webhook_failure_id' => $failure->id,
                'reason' => $failure->status === 'dead_letter' ? 'dead_letter' : 'max_retries_exceeded',
            ]);
            return false;
        }

        try {
            $handler($failure->payload);
            $this->markAsProcessed($failure);
            return true;
        } catch (\Exception $e) {
            $this->markForRetry($failure, $e->getMessage());
            return false;
        }
    }

    /**
     * Clear cache (mostly for testing)
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
}

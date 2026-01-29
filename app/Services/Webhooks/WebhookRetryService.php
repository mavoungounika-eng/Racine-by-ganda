<?php

namespace App\Services\Webhooks;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * WebhookRetryService - Gestion résilience des webhooks
 * 
 * FONCTIONNALITÉS:
 * - Retry avec exponential backoff (2x delay)
 * - Dead letter queue pour failures
 * - Idempotence (pas de double traitement)
 * - Traçabilité complète
 * 
 * USAGE:
 * $service = new WebhookRetryService();
 * $success = $service->retry(
 *     handler: function($payload) { /* process */ },
 *     payload: $webhookData,
 *     maxAttempts: 3,
 *     initialDelay: 1 // secondes
 * );
 */
class WebhookRetryService
{
    private const MAX_ATTEMPTS_DEFAULT = 3;
    private const INITIAL_DELAY_DEFAULT = 1; // seconds
    private const EXPONENTIAL_BASE = 2;

    /**
     * Retry avec exponential backoff
     */
    public function retry(
        callable $handler,
        array $payload,
        int $maxAttempts = self::MAX_ATTEMPTS_DEFAULT,
        int $initialDelay = self::INITIAL_DELAY_DEFAULT,
        ?string $webhookId = null
    ): bool {
        $attempt = 0;
        $delay = $initialDelay;

        while ($attempt < $maxAttempts) {
            try {
                Log::info('[WEBHOOK] Attempt ' . ($attempt + 1) . '/' . $maxAttempts, [
                    'webhook_id' => $webhookId,
                    'delay_seconds' => $delay,
                ]);

                $handler($payload);

                Log::info('[WEBHOOK] Success', [
                    'webhook_id' => $webhookId,
                    'attempt' => $attempt + 1,
                ]);

                return true;

            } catch (Exception $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    // Max attempts atteint → Dead Letter Queue
                    $this->sendToDeadLetterQueue($payload, $e, $webhookId);
                    return false;
                }

                Log::warning('[WEBHOOK] Attempt failed, will retry', [
                    'webhook_id' => $webhookId,
                    'attempt' => $attempt,
                    'error' => $e->getMessage(),
                    'next_delay_seconds' => $delay * self::EXPONENTIAL_BASE,
                ]);

                // Exponential backoff
                sleep($delay);
                $delay *= self::EXPONENTIAL_BASE;
            }
        }

        return false;
    }

    /**
     * Envoyer vers Dead Letter Queue
     */
    private function sendToDeadLetterQueue(
        array $payload,
        Exception $error,
        ?string $webhookId = null
    ): void {
        try {
            DB::table('webhook_failures')->insert([
                'provider' => $payload['provider'] ?? 'unknown',
                'event_type' => $payload['type'] ?? 'unknown',
                'payload' => json_encode($payload),
                'error_message' => $error->getMessage(),
                'retry_count' => 3,
                'last_retry_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::error('[WEBHOOK] Dead Letter Queue entry created', [
                'webhook_id' => $webhookId,
                'provider' => $payload['provider'] ?? 'unknown',
            ]);

        } catch (Exception $e) {
            Log::critical('[WEBHOOK] Failed to write Dead Letter Queue!', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Vérifier et éviter les doublons (idempotence)
     * 
     * Utilise un hash du webhook + timestamp (TTL 5 min)
     */
    public function isIdempotent(array $payload, string $cacheKey, int $ttlMinutes = 5): bool
    {
        $hash = hash('sha256', json_encode($payload));
        $cacheKeyWithHash = $cacheKey . ':' . $hash;

        // Vérifier si déjà traité
        if (cache()->has($cacheKeyWithHash)) {
            Log::warning('[WEBHOOK] Duplicate webhook ignored (idempotence)', [
                'cache_key' => $cacheKeyWithHash,
            ]);
            return false;
        }

        // Marquer comme traité
        cache()->put($cacheKeyWithHash, true, now()->addMinutes($ttlMinutes));
        return true;
    }

    /**
     * Récupérer et rejouer les webhooks en echec
     */
    public function retryFailedWebhooks(
        callable $handler,
        int $limit = 10
    ): int {
        $failed = DB::table('webhook_failures')
            ->where('retry_count', '<', 3)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $successCount = 0;

        foreach ($failed as $entry) {
            try {
                $payload = json_decode($entry->payload, true);
                
                if ($this->retry($handler, $payload, 1)) {
                    DB::table('webhook_failures')->where('id', $entry->id)->delete();
                    $successCount++;
                } else {
                    DB::table('webhook_failures')
                        ->where('id', $entry->id)
                        ->increment('retry_count');
                }
            } catch (Exception $e) {
                Log::error('[WEBHOOK] Failed to retry dead letter', [
                    'id' => $entry->id,
                    'error' => $e->getMessage(),
                ]);

                DB::table('webhook_failures')
                    ->where('id', $entry->id)
                    ->increment('retry_count');
            }
        }

        Log::info('[WEBHOOK] Retry batch complete', [
            'total' => $failed->count(),
            'success' => $successCount,
        ]);

        return $successCount;
    }
}

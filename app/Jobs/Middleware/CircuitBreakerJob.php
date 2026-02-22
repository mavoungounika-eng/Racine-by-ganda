<?php

namespace App\Jobs\Middleware;

use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Queue\QueueMonitor;
use App\Exceptions\CircuitBreakerOpenException;
use Illuminate\Support\Facades\Log;

/**
 * CircuitBreakerJob - Middleware pour circuit breaker des jobs
 * 
 * Vérifie état circuit breaker avant exécution.
 * Enregistre succès/échecs pour tracking.
 * Fail le job si circuit ouvert.
 */
class CircuitBreakerJob
{
    protected QueueCircuitBreaker $circuitBreaker;
    protected QueueMonitor $monitor;

    public function __construct(
        QueueCircuitBreaker $circuitBreaker,
        QueueMonitor $monitor
    ) {
        $this->circuitBreaker = $circuitBreaker;
        $this->monitor = $monitor;
    }

    /**
     * Traiter le job
     */
    public function handle($job, $next)
    {
        $queue = $job->queue ?? 'default';
        $startTime = microtime(true);

        // Vérifier circuit breaker
        if ($this->circuitBreaker->isOpen($queue)) {
            Log::warning('[CIRCUIT BREAKER] Job rejected - circuit open', [
                'job' => get_class($job),
                'queue' => $queue,
            ]);

            $this->monitor->recordJobProcessed($queue, false);

            throw new CircuitBreakerOpenException(
                "Circuit breaker open for queue: {$queue}"
            );
        }

        try {
            // Exécuter job
            $next($job);

            // Succès
            $duration = microtime(true) - $startTime;
            $this->circuitBreaker->recordSuccess($queue);
            $this->monitor->recordProcessingTime($queue, $duration);
            $this->monitor->recordJobProcessed($queue, true);

            Log::debug('[CIRCUIT BREAKER] Job succeeded', [
                'job' => get_class($job),
                'queue' => $queue,
                'duration' => round($duration, 3),
            ]);

        } catch (\Exception $e) {
            // Échec
            $duration = microtime(true) - $startTime;
            $this->circuitBreaker->recordFailure($queue);
            $this->monitor->recordProcessingTime($queue, $duration);
            $this->monitor->recordJobProcessed($queue, false);

            Log::error('[CIRCUIT BREAKER] Job failed', [
                'job' => get_class($job),
                'queue' => $queue,
                'duration' => round($duration, 3),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}

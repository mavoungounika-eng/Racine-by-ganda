<?php

namespace App\Jobs\Middleware;

use App\Services\Queue\QueueRateLimiter;
use Illuminate\Support\Facades\Log;

/**
 * RateLimitedJob - Middleware pour rate limiting des jobs
 * 
 * Applique rate limiting avant exécution du job.
 * Si limite atteinte, release le job avec backoff.
 */
class RateLimitedJob
{
    protected QueueRateLimiter $rateLimiter;

    public function __construct(QueueRateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    /**
     * Traiter le job
     */
    public function handle($job, $next)
    {
        $jobType = $this->getJobType($job);

        if (!$this->rateLimiter->attempt($jobType)) {
            $availableIn = $this->rateLimiter->availableIn($jobType);
            
            Log::debug('[RATE LIMITER] Job rate limited', [
                'job' => get_class($job),
                'job_type' => $jobType,
                'available_in' => $availableIn,
            ]);

            // Release avec backoff
            return $job->release($availableIn);
        }

        $next($job);
    }

    /**
     * Déterminer le type de job
     */
    protected function getJobType($job): string
    {
        // Mapping class => type
        $className = get_class($job);

        return match (true) {
            str_contains($className, 'Webhook') => 'webhooks',
            str_contains($className, 'Email') => 'emails',
            str_contains($className, 'Notification') => 'notifications',
            str_contains($className, 'Pos') => 'pos',
            default => 'default',
        };
    }
}

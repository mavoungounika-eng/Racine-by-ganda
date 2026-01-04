<?php

namespace App\Jobs\AI;

use App\Models\CreatorProfile;
use App\Services\Decision\CreatorDecisionScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeCreatorPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Queue dédiée pour l'IA
     */
    public $queue = 'ai-processing';

    /**
     * Timeout de 5 minutes
     */
    public $timeout = 300;

    /**
     * Nombre de tentatives
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $creatorId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CreatorDecisionScoreService $service): void
    {
        $creator = CreatorProfile::find($this->creatorId);
        
        if (!$creator) {
            \Log::warning("AnalyzeCreatorPerformanceJob: Creator {$this->creatorId} not found");
            return;
        }

        // Le service gère automatiquement le logging et le cache
        $service->calculateDecisionScore($creator);
    }
}

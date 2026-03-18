<?php

namespace App\Jobs\Ai;

use App\Services\Ai\AdminAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAdminSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('ai');
    }

    /**
     * Execute the job.
     */
    public function handle(AdminAiService $service): void
    {
        $service->generateDailySummary();
    }
}

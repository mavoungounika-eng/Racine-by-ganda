<?php

namespace App\Jobs\AI;

use App\Services\Ai\AdminAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAdminSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('ai-processing');
    }

    public function handle(AdminAiService $adminAiService): void
    {
        try {
            $summary = $adminAiService->generateDailySummary();

            Log::info('GenerateAdminSummary job executed.', [
                'summary_length' => mb_strlen($summary),
            ]);
        } catch (\Throwable $e) {
            Log::warning('GenerateAdminSummary job failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

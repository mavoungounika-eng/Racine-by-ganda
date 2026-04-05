<?php

namespace App\Jobs\AI;

use App\Models\User;
use App\Services\Ai\ProductAiService;
use App\Services\CreatorAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeCreatorSales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'ai-processing';
    public int $timeout = 300;
    public int $tries = 3;

    public function handle(
        ProductAiService $productAiService,
        CreatorAnalyticsService $creatorAnalyticsService
    ): void {
        User::query()
            ->whereHas('creatorProfile')
            ->with('creatorProfile')
            ->chunkById(50, function ($creators) use ($productAiService, $creatorAnalyticsService): void {
                foreach ($creators as $creator) {
                    $creatorProfile = $creator->creatorProfile;

                    if (!$creatorProfile) {
                        continue;
                    }

                    try {
                        $salesData = $creatorAnalyticsService->getCreatorMetrics($creatorProfile->id);
                        $productAiService->analyzeSales($creator, $salesData);
                    } catch (\Throwable $e) {
                        Log::warning('AnalyzeCreatorSales job failed for creator.', [
                            'user_id' => $creator->id,
                            'creator_profile_id' => $creatorProfile->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }
}

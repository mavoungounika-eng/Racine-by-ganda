<?php

namespace App\Jobs\AI;

use App\Models\Product;
use App\Services\Ai\ErpAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectStockAnomalies implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('ai-processing');
    }

    public function handle(ErpAiService $erpAiService): void
    {
        $products = Product::query()
            ->whereNotNull('stock')
            ->where('is_active', true)
            ->get(['id', 'title', 'stock']);

        if ($products->isEmpty()) {
            return;
        }

        try {
            $result = $erpAiService->detectStockAnomalies($products);

            Log::info('DetectStockAnomalies job executed.', [
                'products_count' => $products->count(),
                'anomalies_count' => count($result['anomalies'] ?? []),
                'alerts_count' => count($result['alerts'] ?? []),
            ]);
        } catch (\Throwable $e) {
            Log::warning('DetectStockAnomalies job failed.', [
                'products_count' => $products->count(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

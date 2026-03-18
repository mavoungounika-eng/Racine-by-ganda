<?php

namespace App\Jobs\Ai;

use App\Models\Product;
use App\Services\Ai\ErpAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DetectStockAnomalies implements ShouldQueue
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
    public function handle(ErpAiService $service): void
    {
        $products = Product::all(); // You might want to filter this
        $service->detectStockAnomalies($products);
    }
}

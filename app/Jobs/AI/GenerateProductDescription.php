<?php

namespace App\Jobs\Ai;

use App\Models\Product;
use App\Services\Ai\ProductAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProductDescription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public Product $product)
    {
        $this->onQueue('ai');
    }

    /**
     * Execute the job.
     */
    public function handle(ProductAiService $service): void
    {
        $service->generateDescription($this->product);
    }
}

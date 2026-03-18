<?php

namespace App\Jobs\Ai;

use App\Models\User;
use App\Services\Ai\ProductAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeCreatorSales implements ShouldQueue
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
    public function handle(ProductAiService $service): void
    {
        User::where('role', 'createur')->each(function ($creator) use ($service) {
            $service->analyzeSales($creator, []);
        });
    }
}

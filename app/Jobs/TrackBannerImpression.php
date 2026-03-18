<?php

namespace App\Jobs;

use App\Models\Banner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrackBannerImpression implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $banner;

    /**
     * Create a new job instance.
     */
    public function __construct(Banner $banner)
    {
        $this->banner = $banner;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->banner->increment('impressions_count');
    }
}

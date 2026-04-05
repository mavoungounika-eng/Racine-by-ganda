<?php

namespace App\Services\Cms;

use App\Models\Banner;
use App\Jobs\TrackBannerImpression;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BannerService
{
    /**
     * Get active banners for a position with 15min caching.
     */
    public function getActiveBanners(string $position): Collection
    {
        return Cache::remember("cms_banners:{$position}", 900, function () use ($position) {
            return Banner::active()->forPosition($position)
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Record a click on a banner.
     */
    public function recordClick(int $bannerId): void
    {
        Banner::where('id', $bannerId)->increment('clicks_count');
        $this->invalidateBannerCache();
    }

    /**
     * Record an impression (async via Job).
     */
    public function recordImpression(Banner $banner): void
    {
        TrackBannerImpression::dispatch($banner);
    }

    /**
     * Invalidate banner cache.
     */
    public function invalidateBannerCache(): void
    {
        Cache::forget('cms_banners:homepage_hero');
        Cache::forget('cms_banners:homepage_promo');
        Cache::forget('cms_banners:sidebar');
        Cache::forget('cms_banners:category_top');
    }
}

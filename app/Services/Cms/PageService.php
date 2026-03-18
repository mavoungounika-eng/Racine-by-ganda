<?php

namespace App\Services\Cms;

use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PageService
{
    /**
     * Get a published page by its slug with 30min caching.
     */
    public function getPublishedPage(string $slug): ?Page
    {
        return Cache::tags(['cms', 'pages'])->remember("cms_page:{$slug}", 1800, function () use ($slug) {
            return Page::published()->where('slug', $slug)->first();
        });
    }

    /**
     * Get pages for the footer with 1h caching.
     */
    public function getFooterPages(): Collection
    {
        return Cache::tags(['cms', 'pages'])->remember('cms_footer_pages', 3600, function () {
            return Page::published()->forFooter()->get();
        });
    }

    /**
     * Get pages for the header with 1h caching.
     */
    public function getHeaderPages(): Collection
    {
        return Cache::tags(['cms', 'pages'])->remember('cms_header_pages', 3600, function () {
            return Page::published()->forHeader()->get();
        });
    }

    /**
     * Invalidate all page related caches.
     */
    public function invalidatePageCache(Page $page = null): void
    {
        if ($page) {
            Cache::forget("cms_page:{$page->slug}");
        }
        
        Cache::forget('cms_footer_pages');
        Cache::forget('cms_header_pages');
        
        // If driver supports tags, we can also flush by tag
        try {
            Cache::tags(['cms', 'pages'])->flush();
        } catch (\BadMethodCallException $e) {
            // Memory/File driver doesn't support tags
        }
    }
}

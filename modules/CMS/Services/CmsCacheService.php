<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsBlock;
use Modules\CMS\Models\CmsBanner;
use Modules\CMS\Models\CmsEvent;
use Modules\CMS\Models\CmsPortfolio;
use Modules\CMS\Models\CmsAlbum;
use Modules\CMS\Models\CmsFaq;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service de cache pour le module CMS
 * 
 * Gère la mise en cache et l'invalidation des données CMS
 */
class CmsCacheService
{
    /**
     * Durée du cache en minutes (par défaut 60 minutes)
     */
    protected int $cacheDuration = 60;

    /**
     * Récupérer une page CMS avec cache
     * 
     * @param string $slug
     * @return CmsPage|null
     */
    public function getPage(string $slug): ?CmsPage
    {
        $cacheKey = "cms_page_{$slug}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($slug) {
            $page = CmsPage::where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (!$page && config('app.debug')) {
                Log::warning("CMS Page not found: {$slug}");
            }

            return $page;
        });
    }

    /**
     * Récupérer un bloc CMS avec cache
     * 
     * @param string $identifier
     * @return CmsBlock|null
     */
    public function getBlock(string $identifier, ?string $pageSlug = null): ?CmsBlock
    {
        $cacheKey = "cms_block_{$identifier}" . ($pageSlug ? "_{$pageSlug}" : '');

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($identifier, $pageSlug) {
            return CmsBlock::active()
                ->byIdentifier($identifier)
                ->forPage($pageSlug)
                ->first();
        });
    }

    /**
     * Récupérer les bannières actives pour une position avec cache
     * 
     * @param string $position
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBanners(string $position)
    {
        $cacheKey = "cms_banners_{$position}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($position) {
            return CmsBanner::active()
                ->forPosition($position)
                ->get();
        });
    }

    /**
     * Récupérer un événement avec cache
     * 
     * @param string $slug
     * @return CmsEvent|null
     */
    public function getEvent(string $slug): ?CmsEvent
    {
        $cacheKey = "cms_event_{$slug}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($slug) {
            return CmsEvent::where('slug', $slug)
                ->where(function ($query) {
                    $query->where('status', 'upcoming')
                        ->orWhere('status', 'ongoing');
                })
                ->first();
        });
    }

    /**
     * Récupérer les FAQ actives avec cache
     * 
     * @param int|null $categoryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFaqs(?int $categoryId = null)
    {
        $cacheKey = "cms_faqs" . ($categoryId ? "_{$categoryId}" : '');

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($categoryId) {
            $query = CmsFaq::active();

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            return $query->ordered()->get();
        });
    }

    /**
     * Invalider le cache d'une page
     * 
     * @param string $slug
     * @return void
     */
    public function clearPageCache(string $slug): void
    {
        Cache::forget("cms_page_{$slug}");
    }

    /**
     * Invalider le cache d'un bloc
     * 
     * @param string $identifier
     * @param string|null $pageSlug
     * @return void
     */
    public function clearBlockCache(string $identifier, ?string $pageSlug = null): void
    {
        Cache::forget("cms_block_{$identifier}");
        if ($pageSlug) {
            Cache::forget("cms_block_{$identifier}_{$pageSlug}");
        }
    }

    /**
     * Invalider le cache des bannières d'une position
     * 
     * @param string $position
     * @return void
     */
    public function clearBannerCache(string $position): void
    {
        Cache::forget("cms_banners_{$position}");
    }

    /**
     * Invalider le cache d'un événement
     * 
     * @param string $slug
     * @return void
     */
    public function clearEventCache(string $slug): void
    {
        Cache::forget("cms_event_{$slug}");
    }

    /**
     * Invalider le cache des FAQ
     * 
     * @param int|null $categoryId
     * @return void
     */
    public function clearFaqCache(?int $categoryId = null): void
    {
        Cache::forget("cms_faqs");
        if ($categoryId) {
            Cache::forget("cms_faqs_{$categoryId}");
        }
    }

    /**
     * Invalider tout le cache CMS
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        // Invalider pages
        $pages = CmsPage::all();
        foreach ($pages as $page) {
            $this->clearPageCache($page->slug);
        }

        // Invalider blocs
        $blocks = CmsBlock::all();
        foreach ($blocks as $block) {
            $this->clearBlockCache($block->identifier, $block->page_slug);
        }

        // Invalider bannières
        $positions = CmsBanner::distinct()->pluck('position');
        foreach ($positions as $position) {
            $this->clearBannerCache($position);
        }

        // Invalider événements
        $events = CmsEvent::all();
        foreach ($events as $event) {
            $this->clearEventCache($event->slug);
        }

        // Invalider FAQ
        $this->clearFaqCache();
        $categories = \Modules\CMS\Models\CmsFaqCategory::all();
        foreach ($categories as $category) {
            $this->clearFaqCache($category->id);
        }
    }

    /**
     * Définir la durée du cache
     * 
     * @param int $minutes
     * @return self
     */
    public function setCacheDuration(int $minutes): self
    {
        $this->cacheDuration = $minutes;
        return $this;
    }
}


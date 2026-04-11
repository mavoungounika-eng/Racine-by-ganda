<?php

namespace App\Services;

use App\Models\CmsPage;
use App\Models\CmsSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service CMS - Gestion centralisée du contenu CMS
 * 
 * Fournit une interface simple pour récupérer les pages CMS et leurs sections.
 */
class CmsContentService
{
    /**
     * Durée du cache en minutes (par défaut 60 minutes)
     */
    protected int $cacheDuration = 60;

    /**
     * Récupérer une page CMS par son slug avec ses sections actives
     * 
     * @param string $slug
     * @param bool $withSections
     * @return CmsPage|null
     */
    public function getPage(string $slug, bool $withSections = true): ?CmsPage
    {
        $cacheKey = "cms_page_{$slug}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($slug, $withSections) {
            $query = CmsPage::published()->bySlug($slug);

            if ($withSections) {
                $query->with(['sections' => function ($query) {
                    $query->active()->ordered();
                }]);
            }

            $page = $query->first();

            if (!$page && config('app.debug')) {
                Log::warning("CMS Page not found: {$slug}");
            }

            return $page;
        });
    }

    /**
     * Récupérer une section spécifique d'une page
     * 
     * @param string $pageSlug
     * @param string $sectionKey
     * @return CmsSection|null
     */
    public function getSection(string $pageSlug, string $sectionKey): ?CmsSection
    {
        $cacheKey = "cms_section_{$pageSlug}_{$sectionKey}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($pageSlug, $sectionKey) {
            return CmsSection::active()
                ->forPage($pageSlug)
                ->where('key', $sectionKey)
                ->first();
        });
    }

    /**
     * Récupérer toutes les sections actives d'une page
     * 
     * @param string $pageSlug
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSections(string $pageSlug)
    {
        $cacheKey = "cms_sections_{$pageSlug}";

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheDuration), function () use ($pageSlug) {
            return CmsSection::active()
                ->forPage($pageSlug)
                ->ordered()
                ->get();
        });
    }

    /**
     * Vérifier si une page existe et est publiée
     * 
     * @param string $slug
     * @return bool
     */
    public function pageExists(string $slug): bool
    {
        return CmsPage::published()->bySlug($slug)->exists();
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
        Cache::forget("cms_sections_{$slug}");
        
        // Invalider aussi les caches de sections individuelles
        $sections = CmsSection::forPage($slug)->get();
        foreach ($sections as $section) {
            Cache::forget("cms_section_{$slug}_{$section->key}");
        }
    }

    /**
     * Invalider tout le cache CMS
     * 
     * @return void
     */
    public function clearAllCache(): void
    {
        // Note: En production, on pourrait utiliser un tag de cache
        // Pour l'instant, on invalide page par page
        $pages = CmsPage::all();
        foreach ($pages as $page) {
            $this->clearPageCache($page->slug);
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


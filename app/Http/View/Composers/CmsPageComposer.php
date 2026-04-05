<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\Cms\PageService;

class CmsPageComposer
{
    /**
     * Mapping view name → CMS page slug (when they differ).
     */
    private const SLUG_MAP = [
        'shop'       => 'boutique',
        'about'      => 'a-propos',
        'help'       => 'aide',
        'shipping'   => 'livraison',
        'returns'    => 'retours-echanges',
        'terms'      => 'cgv',
        'privacy'    => 'confidentialite',
        'creators'   => 'createurs',
        'events'     => 'evenements',
        'ceo'        => 'amira-ganda',
    ];

    public function __construct(private PageService $pageService) {}

    public function compose(View $view): void
    {
        if ($view->offsetExists('cmsPage')) {
            return;
        }

        try {
            $viewName = $view->getName();
            $viewSlug = str_replace('frontend.', '', $viewName);
            $slug = self::SLUG_MAP[$viewSlug] ?? $viewSlug;

            $cmsPage = $this->pageService->getPublishedPage($slug);
            $view->with('cmsPage', $cmsPage);
        } catch (\Throwable $e) {
            $view->with('cmsPage', null);
        }
    }
}

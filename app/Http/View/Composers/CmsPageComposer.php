<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\Cms\PageService;

class CmsPageComposer
{
    public function __construct(private PageService $pageService) {}

    public function compose(View $view): void
    {
        // Don't override if already set by a controller
        if ($view->offsetExists('cmsPage')) {
            return;
        }

        try {
            // Extract slug from view name: "frontend.shop" → "shop"
            $viewName = $view->getName();
            $slug = str_replace('frontend.', '', $viewName);

            $cmsPage = $this->pageService->getPublishedPage($slug);
            $view->with('cmsPage', $cmsPage);
        } catch (\Throwable $e) {
            $view->with('cmsPage', null);
        }
    }
}

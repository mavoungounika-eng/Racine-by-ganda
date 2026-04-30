<?php

namespace App\Observers;

use App\Models\Page;
use App\Services\Cms\PageService;

class PageObserver
{
    protected $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * Handle the Page "saved" event.
     */
    public function saved(Page $page): void
    {
        $this->pageService->invalidatePageCache($page);
    }

    /**
     * Handle the Page "deleted" event.
     */
    public function deleted(Page $page): void
    {
        $this->pageService.invalidatePageCache($page);
    }
}

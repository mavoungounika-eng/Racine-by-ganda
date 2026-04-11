<?php
namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\Cms\PageService;
use App\Services\Cms\CategoryService;

class CmsViewComposer
{
  public function __construct(
    private PageService     $pageService,
    private CategoryService $categoryService,
  ) {}

  public function compose(View $view): void
  {
    try {
      $view->with('cmsFooterPages',
        $this->pageService->getFooterPages());
      $view->with('cmsHeaderPages',
        $this->pageService->getHeaderPages());
      $view->with('cmsNavCategories',
        $this->categoryService->getTree());
    } catch (\Throwable $e) {
      // Fallback silencieux — jamais crasher le layout
      $view->with('cmsFooterPages', collect());
      $view->with('cmsHeaderPages', collect());
      $view->with('cmsNavCategories', []);
    }
  }
}

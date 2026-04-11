<?php
namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\Cms\BannerService;
use App\Services\Cms\ContentBlockService;

class HomeComposer
{
  public function __construct(
    private BannerService       $bannerService,
    private ContentBlockService $blockService,
  ) {}

  public function compose(View $view): void
  {
    try {
      $view->with('cmsHeroBanners',
        $this->bannerService
          ->getActiveBanners('homepage_hero'));
      $view->with('cmsPromoBanners',
        $this->bannerService
          ->getActiveBanners('homepage_promo'));
      $view->with('cmsBlocks',
        $this->blockService->getBlocks([
          'home_intro', 'home_features', 'home_cta'
        ]));
    } catch (\Throwable $e) {
      $view->with('cmsHeroBanners', collect());
      $view->with('cmsPromoBanners', collect());
      $view->with('cmsBlocks', []);
    }
  }
}

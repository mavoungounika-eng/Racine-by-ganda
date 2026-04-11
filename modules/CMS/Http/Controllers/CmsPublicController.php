<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsEvent;
use Modules\CMS\Models\CmsPortfolio;
use Modules\CMS\Models\CmsAlbum;
use Modules\CMS\Services\CmsCacheService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\Response;

/**
 * Contrôleur public pour afficher le contenu CMS sur le frontend
 */
class CmsPublicController extends Controller
{
    protected CmsCacheService $cacheService;

    public function __construct(CmsCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Afficher une page CMS publique
     */
    public function showPage(string $slug): View|Response
    {
        $page = $this->cacheService->getPage($slug);

        if (!$page) {
            abort(404, 'Page non trouvée');
        }

        return view('cms::public.page', compact('page'));
    }

    /**
     * Afficher un événement CMS public
     */
    public function showEvent(string $slug): View|Response
    {
        $event = CmsEvent::where('slug', $slug)
            ->where(function ($query) {
                $query->where('status', 'upcoming')
                    ->orWhere('status', 'ongoing');
            })
            ->first();

        if (!$event) {
            abort(404, 'Événement non trouvé');
        }

        return view('cms::public.event', compact('event'));
    }

    /**
     * Afficher un projet portfolio public
     */
    public function showPortfolio(string $slug): View|Response
    {
        $portfolio = CmsPortfolio::where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$portfolio) {
            abort(404, 'Projet non trouvé');
        }

        return view('cms::public.portfolio', compact('portfolio'));
    }

    /**
     * Afficher un album public
     */
    public function showAlbum(string $slug): View|Response
    {
        $album = CmsAlbum::where('slug', $slug)
            ->where('status', 'published')
            ->first();

        if (!$album) {
            abort(404, 'Album non trouvé');
        }

        return view('cms::public.album', compact('album'));
    }
}


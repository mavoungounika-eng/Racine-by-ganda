<?php

namespace App\Http\Controllers;

use App\Services\Cms\PageService;
use Illuminate\Http\Request;

class PageController extends Controller
{
    protected $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    /**
     * Display the specified page.
     */
    public function show(string $slug)
    {
        $page = $this->pageService->getPublishedPage($slug);
        
        abort_if(!$page, 404);

        $template = match($page->template) {
            'full_width' => 'cms.pages.full-width',
            'sidebar'    => 'cms.pages.sidebar',
            default      => 'cms.pages.default',
        };

        return view($template, compact('page'));
    }
}

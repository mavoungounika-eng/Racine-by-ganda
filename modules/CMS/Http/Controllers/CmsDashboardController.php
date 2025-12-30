<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsBlock;
use Modules\CMS\Models\CmsMedia;
use Modules\CMS\Models\CmsFaq;
use Modules\CMS\Models\CmsBanner;

class CmsDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'pages' => [
                'total' => CmsPage::count(),
                'published' => CmsPage::where('status', 'published')->count(),
                'draft' => CmsPage::where('status', 'draft')->count(),
            ],
            'blocks' => [
                'total' => CmsBlock::count(),
                'active' => CmsBlock::where('is_active', true)->count(),
            ],
            'media' => [
                'total' => CmsMedia::count(),
                'images' => CmsMedia::images()->count(),
                'size' => CmsMedia::sum('size'),
            ],
            'faq' => [
                'total' => CmsFaq::count(),
                'active' => CmsFaq::where('is_active', true)->count(),
            ],
            'banners' => [
                'total' => CmsBanner::count(),
                'active' => CmsBanner::where('is_active', true)->count(),
            ],
        ];
        
        $recentPages = CmsPage::orderBy('updated_at', 'desc')->take(5)->get();
        $recentMedia = CmsMedia::orderBy('created_at', 'desc')->take(8)->get();
        
        return view('cms::dashboard', compact('stats', 'recentPages', 'recentMedia'));
    }
}


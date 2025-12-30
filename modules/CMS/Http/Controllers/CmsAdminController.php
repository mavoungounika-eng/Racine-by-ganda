<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsEvent;
use Modules\CMS\Models\CmsPortfolio;
use Modules\CMS\Models\CmsAlbum;
use Modules\CMS\Models\CmsBanner;
use Modules\CMS\Models\CmsSetting;
use Modules\CMS\Services\CmsCacheService;
use Illuminate\Support\Str;

class CmsAdminController extends Controller
{
    protected CmsCacheService $cacheService;

    public function __construct(CmsCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    // ========================================
    // DASHBOARD CMS
    // ========================================
    
    public function index()
    {
        $stats = [
            'pages' => CmsPage::count(),
            'events' => CmsEvent::count(),
            'portfolio' => CmsPortfolio::count(),
            'albums' => CmsAlbum::count(),
            'banners' => CmsBanner::where('is_active', true)->count(),
        ];
        
        $recentPages = CmsPage::latest()->limit(5)->get();
        $upcomingEvents = CmsEvent::upcoming()->limit(5)->get();
        
        return view('cms::admin.dashboard', compact('stats', 'recentPages', 'upcomingEvents'));
    }
    
    // ========================================
    // PAGES
    // ========================================
    
    public function pages()
    {
        $pages = CmsPage::with('author')->latest()->paginate(15);
        return view('cms::admin.pages.index', compact('pages'));
    }
    
    public function createPage()
    {
        return view('cms::admin.pages.create');
    }
    
    public function storePage(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug',
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'template' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
        ]);
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('cms/pages', 'public');
        }
        
        $validated['author_id'] = auth()->id();
        $validated['meta'] = [
            'title' => $validated['meta_title'] ?? null,
            'description' => $validated['meta_description'] ?? null,
        ];
        
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }
        
        $page = CmsPage::create($validated);
        
        // Invalider le cache
        if ($page->status === 'published') {
            $this->cacheService->clearPageCache($page->slug);
        }
        
        return redirect()->route('cms.admin.pages')->with('success', 'Page créée avec succès.');
    }
    
    public function editPage(CmsPage $page)
    {
        return view('cms::admin.pages.edit', compact('page'));
    }
    
    public function updatePage(Request $request, CmsPage $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug,' . $page->id,
            'excerpt' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'template' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
        ]);
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('cms/pages', 'public');
        }
        
        if ($validated['status'] === 'published' && !$page->published_at) {
            $validated['published_at'] = now();
        }
        
        $oldSlug = $page->slug;
        $page->update($validated);
        
        // Invalider le cache
        $this->cacheService->clearPageCache($oldSlug);
        if ($page->slug !== $oldSlug) {
            $this->cacheService->clearPageCache($page->slug);
        }
        
        return redirect()->route('cms.admin.pages')->with('success', 'Page mise à jour avec succès.');
    }
    
    public function destroyPage(CmsPage $page)
    {
        $slug = $page->slug;
        $page->delete();
        
        // Invalider le cache
        $this->cacheService->clearPageCache($slug);
        
        return redirect()->route('cms.admin.pages')->with('success', 'Page supprimée avec succès.');
    }
    
    // ========================================
    // EVENTS
    // ========================================
    
    public function events()
    {
        $events = CmsEvent::latest('start_date')->paginate(15);
        return view('cms::admin.events.index', compact('events'));
    }
    
    public function createEvent()
    {
        return view('cms::admin.events.create');
    }
    
    public function storeEvent(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_events,slug',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'required|in:fashion_show,exhibition,workshop,sale,meeting,other',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
            'capacity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'is_free' => 'boolean',
            'registration_required' => 'boolean',
        ]);
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('cms/events', 'public');
        }
        
        $event = CmsEvent::create($validated);
        
        // Invalider le cache
        $this->cacheService->clearEventCache($event->slug);
        
        return redirect()->route('cms.admin.events')->with('success', 'Événement créé avec succès.');
    }
    
    public function editEvent(CmsEvent $event)
    {
        return view('cms::admin.events.edit', compact('event'));
    }
    
    public function updateEvent(Request $request, CmsEvent $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_events,slug,' . $event->id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'location' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'required|in:fashion_show,exhibition,workshop,sale,meeting,other',
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
            'capacity' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
            'is_free' => 'boolean',
            'registration_required' => 'boolean',
        ]);
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('cms/events', 'public');
        }
        
        $oldSlug = $event->slug;
        $event->update($validated);
        
        // Invalider le cache
        $this->cacheService->clearEventCache($oldSlug);
        if ($event->slug !== $oldSlug) {
            $this->cacheService->clearEventCache($event->slug);
        }
        
        return redirect()->route('cms.admin.events')->with('success', 'Événement mis à jour avec succès.');
    }
    
    public function destroyEvent(CmsEvent $event)
    {
        $slug = $event->slug;
        $event->delete();
        
        // Invalider le cache
        $this->cacheService->clearEventCache($slug);
        
        return redirect()->route('cms.admin.events')->with('success', 'Événement supprimé avec succès.');
    }
    
    // ========================================
    // PORTFOLIO
    // ========================================
    
    public function portfolio()
    {
        $items = CmsPortfolio::latest()->paginate(15);
        return view('cms::admin.portfolio.index', compact('items'));
    }
    
    public function createPortfolio()
    {
        return view('cms::admin.portfolio.create');
    }
    
    public function storePortfolio(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_portfolio,slug',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'gallery.*' => 'nullable|image|max:2048',
            'category' => 'nullable|string|max:100',
            'client' => 'nullable|string|max:255',
            'project_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
        ]);
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('cms/portfolio', 'public');
        }
        
        $gallery = [];
        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $image) {
                $gallery[] = $image->store('cms/portfolio/gallery', 'public');
            }
        }
        $validated['gallery'] = $gallery;
        
        CmsPortfolio::create($validated);
        
        return redirect()->route('cms.admin.portfolio')->with('success', 'Projet ajouté au portfolio.');
    }
    
    public function editPortfolio(CmsPortfolio $portfolio)
    {
        return view('cms::admin.portfolio.edit', compact('portfolio'));
    }
    
    public function updatePortfolio(Request $request, CmsPortfolio $portfolio)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_portfolio,slug,' . $portfolio->id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:2048',
            'category' => 'nullable|string|max:100',
            'client' => 'nullable|string|max:255',
            'project_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
        ]);
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('cms/portfolio', 'public');
        }
        
        $portfolio->update($validated);
        
        return redirect()->route('cms.admin.portfolio')->with('success', 'Projet mis à jour.');
    }
    
    public function destroyPortfolio(CmsPortfolio $portfolio)
    {
        $portfolio->delete();
        return redirect()->route('cms.admin.portfolio')->with('success', 'Projet supprimé du portfolio.');
    }
    
    // ========================================
    // ALBUMS
    // ========================================
    
    public function albums()
    {
        $albums = CmsAlbum::latest()->paginate(15);
        return view('cms::admin.albums.index', compact('albums'));
    }
    
    public function createAlbum()
    {
        return view('cms::admin.albums.create');
    }
    
    public function storeAlbum(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_albums,slug',
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'photos.*' => 'nullable|image|max:2048',
            'category' => 'nullable|string|max:100',
            'album_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('cms/albums', 'public');
        }
        
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = [
                    'path' => $photo->store('cms/albums/photos', 'public'),
                    'caption' => '',
                ];
            }
        }
        $validated['photos'] = $photos;
        
        $album = CmsAlbum::create($validated);
        
        // Pas de cache pour albums (liste uniquement)
        
        return redirect()->route('cms.admin.albums')->with('success', 'Album créé avec succès.');
    }
    
    public function editAlbum(CmsAlbum $album)
    {
        return view('cms::admin.albums.edit', compact('album'));
    }
    
    public function updateAlbum(Request $request, CmsAlbum $album)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_albums,slug,' . $album->id,
            'description' => 'nullable|string',
            'cover_image' => 'nullable|image|max:2048',
            'category' => 'nullable|string|max:100',
            'album_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);
        
        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $request->file('cover_image')->store('cms/albums', 'public');
        }
        
        $album->update($validated);
        
        return redirect()->route('cms.admin.albums')->with('success', 'Album mis à jour.');
    }
    
    public function destroyAlbum(CmsAlbum $album)
    {
        $album->delete();
        return redirect()->route('cms.admin.albums')->with('success', 'Album supprimé.');
    }
    
    // ========================================
    // BANNERS
    // ========================================
    
    public function banners()
    {
        $banners = CmsBanner::orderBy('position')->orderBy('order')->paginate(15);
        return view('cms::admin.banners.index', compact('banners'));
    }
    
    public function createBanner()
    {
        return view('cms::admin.banners.create');
    }
    
    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'image' => 'required|image|max:2048',
            'image_mobile' => 'nullable|image|max:2048',
            'link_url' => 'nullable|url',
            'link_text' => 'nullable|string|max:100',
            'position' => 'required|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        $validated['image'] = $request->file('image')->store('cms/banners', 'public');
        
        if ($request->hasFile('image_mobile')) {
            $validated['image_mobile'] = $request->file('image_mobile')->store('cms/banners', 'public');
        }
        
        CmsBanner::create($validated);
        
        return redirect()->route('cms.admin.banners')->with('success', 'Bannière créée avec succès.');
    }
    
    public function editBanner(CmsBanner $banner)
    {
        return view('cms::admin.banners.edit', compact('banner'));
    }
    
    public function updateBanner(Request $request, CmsBanner $banner)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'image_mobile' => 'nullable|image|max:2048',
            'link_url' => 'nullable|url',
            'link_text' => 'nullable|string|max:100',
            'position' => 'required|string|max:100',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('cms/banners', 'public');
        }
        
        if ($request->hasFile('image_mobile')) {
            $validated['image_mobile'] = $request->file('image_mobile')->store('cms/banners', 'public');
        }
        
        $banner->update($validated);
        
        return redirect()->route('cms.admin.banners')->with('success', 'Bannière mise à jour.');
    }
    
    public function destroyBanner(CmsBanner $banner)
    {
        $banner->delete();
        return redirect()->route('cms.admin.banners')->with('success', 'Bannière supprimée.');
    }
    
    // ========================================
    // SETTINGS
    // ========================================
    
    public function settings()
    {
        $settings = CmsSetting::all()->groupBy('group');
        return view('cms::admin.settings', compact('settings'));
    }
    
    public function updateSettings(Request $request)
    {
        foreach ($request->settings as $key => $value) {
            CmsSetting::set($key, $value);
        }
        
        return redirect()->route('cms.admin.settings')->with('success', 'Paramètres mis à jour.');
    }
}


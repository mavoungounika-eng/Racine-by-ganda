<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\CMS\Models\CmsPage;
use Modules\CMS\Models\CmsEvent;
use Modules\CMS\Models\CmsPortfolio;
use Modules\CMS\Models\CmsAlbum;
use Modules\CMS\Models\CmsBanner;
use Modules\CMS\Models\CmsBlock;
use Modules\CMS\Models\CmsFaq;
use Modules\CMS\Models\CmsFaqCategory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Contrôleur API REST pour le module CMS
 */
class CmsApiController extends Controller
{
    // ========================================
    // PAGES
    // ========================================
    
    public function pages(Request $request): JsonResponse
    {
        $query = CmsPage::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        $pages = $query->latest()->paginate($request->get('per_page', 15));
        
        return response()->json($pages);
    }
    
    public function showPage(CmsPage $page): JsonResponse
    {
        return response()->json($page);
    }
    
    public function storePage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'template' => 'required|string',
            'status' => 'required|in:draft,published,archived',
        ]);
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        $validated['created_by'] = $request->user()->id;
        $validated['updated_by'] = $request->user()->id;
        
        $page = CmsPage::create($validated);
        
        return response()->json($page, 201);
    }
    
    public function updatePage(Request $request, CmsPage $page): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug,' . $page->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'template' => 'required|string',
            'status' => 'required|in:draft,published,archived',
        ]);
        
        $validated['updated_by'] = $request->user()->id;
        
        $page->update($validated);
        
        return response()->json($page);
    }
    
    public function destroyPage(CmsPage $page): JsonResponse
    {
        $page->delete();
        
        return response()->json(['message' => 'Page supprimée avec succès'], 200);
    }
    
    // ========================================
    // EVENTS
    // ========================================
    
    public function events(Request $request): JsonResponse
    {
        $query = CmsEvent::query();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $events = $query->latest()->paginate($request->get('per_page', 15));
        
        return response()->json($events);
    }
    
    public function showEvent(CmsEvent $event): JsonResponse
    {
        return response()->json($event);
    }
    
    public function storeEvent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_events,slug',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:workshop,exhibition,launch,other',
            'status' => 'required|in:upcoming,ongoing,past,cancelled',
        ]);
        
        $event = CmsEvent::create($validated);
        
        return response()->json($event, 201);
    }
    
    public function updateEvent(Request $request, CmsEvent $event): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_events,slug,' . $event->id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'type' => 'required|in:workshop,exhibition,launch,other',
            'status' => 'required|in:upcoming,ongoing,past,cancelled',
        ]);
        
        $event->update($validated);
        
        return response()->json($event);
    }
    
    public function destroyEvent(CmsEvent $event): JsonResponse
    {
        $event->delete();
        
        return response()->json(['message' => 'Événement supprimé avec succès'], 200);
    }
    
    // ========================================
    // PORTFOLIO
    // ========================================
    
    public function portfolio(Request $request): JsonResponse
    {
        $items = CmsPortfolio::latest()->paginate($request->get('per_page', 15));
        
        return response()->json($items);
    }
    
    public function showPortfolio(CmsPortfolio $portfolio): JsonResponse
    {
        return response()->json($portfolio);
    }
    
    public function storePortfolio(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_portfolio,slug',
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'client' => 'nullable|string|max:255',
            'project_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
        ]);
        
        $portfolio = CmsPortfolio::create($validated);
        
        return response()->json($portfolio, 201);
    }
    
    public function updatePortfolio(Request $request, CmsPortfolio $portfolio): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_portfolio,slug,' . $portfolio->id,
            'description' => 'nullable|string',
            'content' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'client' => 'nullable|string|max:255',
            'project_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
        ]);
        
        $portfolio->update($validated);
        
        return response()->json($portfolio);
    }
    
    public function destroyPortfolio(CmsPortfolio $portfolio): JsonResponse
    {
        $portfolio->delete();
        
        return response()->json(['message' => 'Projet portfolio supprimé avec succès'], 200);
    }
    
    // ========================================
    // ALBUMS
    // ========================================
    
    public function albums(Request $request): JsonResponse
    {
        $albums = CmsAlbum::latest()->paginate($request->get('per_page', 15));
        
        return response()->json($albums);
    }
    
    public function showAlbum(CmsAlbum $album): JsonResponse
    {
        return response()->json($album);
    }
    
    public function storeAlbum(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_albums,slug',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'album_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);
        
        $album = CmsAlbum::create($validated);
        
        return response()->json($album, 201);
    }
    
    public function updateAlbum(Request $request, CmsAlbum $album): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_albums,slug,' . $album->id,
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'album_date' => 'nullable|date',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
        ]);
        
        $album->update($validated);
        
        return response()->json($album);
    }
    
    public function destroyAlbum(CmsAlbum $album): JsonResponse
    {
        $album->delete();
        
        return response()->json(['message' => 'Album supprimé avec succès'], 200);
    }
    
    // ========================================
    // BANNERS
    // ========================================
    
    public function banners(Request $request): JsonResponse
    {
        $query = CmsBanner::query();
        
        if ($request->has('position')) {
            $query->where('position', $request->position);
        }
        
        $banners = $query->latest()->paginate($request->get('per_page', 15));
        
        return response()->json($banners);
    }
    
    public function showBanner(CmsBanner $banner): JsonResponse
    {
        return response()->json($banner);
    }
    
    public function storeBanner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        $banner = CmsBanner::create($validated);
        
        return response()->json($banner, 201);
    }
    
    public function updateBanner(Request $request, CmsBanner $banner): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        $banner->update($validated);
        
        return response()->json($banner);
    }
    
    public function destroyBanner(CmsBanner $banner): JsonResponse
    {
        $banner->delete();
        
        return response()->json(['message' => 'Bannière supprimée avec succès'], 200);
    }
    
    // ========================================
    // BLOCKS
    // ========================================
    
    public function blocks(Request $request): JsonResponse
    {
        $query = CmsBlock::query();
        
        if ($request->has('zone')) {
            $query->where('zone', $request->zone);
        }
        
        $blocks = $query->latest()->paginate($request->get('per_page', 15));
        
        return response()->json($blocks);
    }
    
    public function showBlock(CmsBlock $block): JsonResponse
    {
        return response()->json($block);
    }
    
    public function storeBlock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:cms_blocks,identifier',
            'content' => 'nullable|string',
            'type' => 'required|string',
            'zone' => 'required|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        $block = CmsBlock::create($validated);
        
        return response()->json($block, 201);
    }
    
    public function updateBlock(Request $request, CmsBlock $block): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:cms_blocks,identifier,' . $block->id,
            'content' => 'nullable|string',
            'type' => 'required|string',
            'zone' => 'required|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        $block->update($validated);
        
        return response()->json($block);
    }
    
    public function destroyBlock(CmsBlock $block): JsonResponse
    {
        $block->delete();
        
        return response()->json(['message' => 'Bloc supprimé avec succès'], 200);
    }
    
    // ========================================
    // FAQ
    // ========================================
    
    public function faq(Request $request): JsonResponse
    {
        $query = CmsFaq::with('category');
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        
        $faqs = $query->latest()->paginate($request->get('per_page', 15));
        
        return response()->json($faqs);
    }
    
    public function showFaq(CmsFaq $faq): JsonResponse
    {
        return response()->json($faq->load('category'));
    }
    
    public function storeFaq(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:cms_faq_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $faq = CmsFaq::create($validated);
        
        return response()->json($faq, 201);
    }
    
    public function updateFaq(Request $request, CmsFaq $faq): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:cms_faq_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $faq->update($validated);
        
        return response()->json($faq);
    }
    
    public function destroyFaq(CmsFaq $faq): JsonResponse
    {
        $faq->delete();
        
        return response()->json(['message' => 'FAQ supprimée avec succès'], 200);
    }
    
    // ========================================
    // FAQ CATEGORIES
    // ========================================
    
    public function faqCategories(): JsonResponse
    {
        $categories = CmsFaqCategory::withCount('faqs')->orderBy('order')->get();
        
        return response()->json($categories);
    }
    
    public function storeFaqCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_faq_categories,slug',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'order' => 'integer',
        ]);
        
        $category = CmsFaqCategory::create($validated);
        
        return response()->json($category, 201);
    }
    
    public function updateFaqCategory(Request $request, CmsFaqCategory $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_faq_categories,slug,' . $category->id,
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);
        
        $category->update($validated);
        
        return response()->json($category);
    }
    
    public function destroyFaqCategory(CmsFaqCategory $category): JsonResponse
    {
        $category->delete();
        
        return response()->json(['message' => 'Catégorie FAQ supprimée avec succès'], 200);
    }
    
    // ========================================
    // UPLOAD IMAGE (pour TinyMCE)
    // ========================================
    
    public function uploadImage(Request $request): JsonResponse
    {
        // Vérifier l'authentification (web auth pour CSRF)
        if (!auth()->check()) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }
        
        $request->validate([
            'file' => 'required|image|max:5120', // 5MB max
        ]);
        
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('cms/uploads', $filename, 'public');
            
            $url = Storage::url($path);
            
            return response()->json([
                'location' => asset($url)
            ]);
        }
        
        return response()->json(['error' => 'Aucun fichier fourni'], 400);
    }
}


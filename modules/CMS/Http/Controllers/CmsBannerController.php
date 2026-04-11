<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CMS\Models\CmsBanner;
use Modules\CMS\Services\CmsCacheService;

class CmsBannerController extends Controller
{
    protected CmsCacheService $cacheService;

    public function __construct(CmsCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request)
    {
        $query = CmsBanner::query();
        
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }
        
        $banners = $query->orderBy('position')->orderBy('order')->paginate(15);
        $positions = [
            'homepage_hero' => 'Hero Page d\'accueil',
            'homepage_bottom' => 'Bas Page d\'accueil',
            'sidebar_top' => 'Haut Sidebar',
            'sidebar_bottom' => 'Bas Sidebar',
            'footer_top' => 'Haut Footer',
            'popup' => 'Popup',
        ];
        
        return view('cms::admin.banners.index', compact('banners', 'positions'));
    }
    
    public function create()
    {
        $positions = [
            'homepage_hero' => 'Hero Page d\'accueil',
            'homepage_bottom' => 'Bas Page d\'accueil',
            'sidebar_top' => 'Haut Sidebar',
            'sidebar_bottom' => 'Bas Sidebar',
            'footer_top' => 'Haut Footer',
            'popup' => 'Popup',
        ];
        
        return view('cms::admin.banners.create', compact('positions'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'required|image|max:5120',
            'image_mobile' => 'nullable|image|max:2048',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'cta_style' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        // Upload images
        $validated['image'] = $request->file('image')->store('cms/banners', 'public');
        
        if ($request->hasFile('image_mobile')) {
            $validated['image_mobile'] = $request->file('image_mobile')
                ->store('cms/banners', 'public');
        }
        
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $banner = CmsBanner::create($validated);
        
        // Invalider le cache
        $this->cacheService->clearBannerCache($banner->position);
        
        return redirect()->route('cms.admin.banners')
            ->with('success', 'Bannière créée avec succès !');
    }
    
    public function edit(CmsBanner $banner)
    {
        $positions = [
            'homepage_hero' => 'Hero Page d\'accueil',
            'homepage_bottom' => 'Bas Page d\'accueil',
            'sidebar_top' => 'Haut Sidebar',
            'sidebar_bottom' => 'Bas Sidebar',
            'footer_top' => 'Haut Footer',
            'popup' => 'Popup',
        ];
        
        return view('cms::admin.banners.edit', compact('banner', 'positions'));
    }
    
    public function update(Request $request, CmsBanner $banner)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'position' => 'required|string',
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|max:5120',
            'image_mobile' => 'nullable|image|max:2048',
            'cta_text' => 'nullable|string|max:100',
            'cta_link' => 'nullable|string|max:255',
            'cta_style' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('cms/banners', 'public');
        }
        
        if ($request->hasFile('image_mobile')) {
            $validated['image_mobile'] = $request->file('image_mobile')
                ->store('cms/banners', 'public');
        }
        
        $oldPosition = $banner->position;
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $banner->update($validated);
        
        // Invalider le cache
        $this->cacheService->clearBannerCache($oldPosition);
        if ($banner->position !== $oldPosition) {
            $this->cacheService->clearBannerCache($banner->position);
        }
        
        return redirect()->route('cms.admin.banners')
            ->with('success', 'Bannière mise à jour !');
    }
    
    public function destroy(CmsBanner $banner)
    {
        $position = $banner->position;
        $banner->delete();
        
        // Invalider le cache
        $this->cacheService->clearBannerCache($position);
        
        return redirect()->route('cms.admin.banners')
            ->with('success', 'Bannière supprimée !');
    }
    
    public function toggle(CmsBanner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        
        // Invalider le cache
        $this->cacheService->clearBannerCache($banner->position);
        
        return back()->with('success', 
            $banner->is_active ? 'Bannière activée !' : 'Bannière désactivée !');
    }
}


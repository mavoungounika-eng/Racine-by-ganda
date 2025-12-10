<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\CMS\Models\CmsBlock;
use Modules\CMS\Services\CmsCacheService;

class CmsBlockController extends Controller
{
    protected CmsCacheService $cacheService;

    public function __construct(CmsCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request)
    {
        $query = CmsBlock::query();
        
        if ($request->filled('zone')) {
            $query->where('zone', $request->zone);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('identifier', 'like', '%' . $request->search . '%');
            });
        }
        
        $blocks = $query->orderBy('zone')->orderBy('order')->paginate(20);
        
        $zones = [
            'header' => 'Header',
            'content' => 'Contenu',
            'sidebar' => 'Sidebar',
            'footer' => 'Footer',
            'popup' => 'Popup',
        ];
        
        return view('cms::admin.blocks.index', compact('blocks', 'zones'));
    }
    
    public function create()
    {
        $zones = [
            'header' => 'Header',
            'content' => 'Contenu',
            'sidebar' => 'Sidebar',
            'footer' => 'Footer',
            'popup' => 'Popup',
        ];
        
        $types = [
            'html' => 'HTML',
            'json' => 'JSON (Données structurées)',
            'image' => 'Image',
            'gallery' => 'Galerie',
            'text' => 'Texte simple',
        ];
        
        return view('cms::admin.blocks.create', compact('zones', 'types'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:cms_blocks,identifier',
            'type' => 'required|string',
            'zone' => 'required|string',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'page_slug' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        $validated['updated_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['settings'] = $request->input('settings', []);
        
        $block = CmsBlock::create($validated);
        
        // Invalider le cache
        $this->cacheService->clearBlockCache($block->identifier, $block->page_slug);
        
        return redirect()->route('cms.admin.blocks.index')
            ->with('success', 'Bloc créé avec succès !');
    }
    
    public function edit(CmsBlock $block)
    {
        $zones = [
            'header' => 'Header',
            'content' => 'Contenu',
            'sidebar' => 'Sidebar',
            'footer' => 'Footer',
            'popup' => 'Popup',
        ];
        
        $types = [
            'html' => 'HTML',
            'json' => 'JSON (Données structurées)',
            'image' => 'Image',
            'gallery' => 'Galerie',
            'text' => 'Texte simple',
        ];
        
        return view('cms::admin.blocks.edit', compact('block', 'zones', 'types'));
    }
    
    public function update(Request $request, CmsBlock $block)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'identifier' => 'required|string|max:255|unique:cms_blocks,identifier,' . $block->id,
            'type' => 'required|string',
            'zone' => 'required|string',
            'content' => 'nullable|string',
            'settings' => 'nullable|array',
            'page_slug' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);
        
        $validated['updated_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $oldIdentifier = $block->identifier;
        $oldPageSlug = $block->page_slug;
        $block->update($validated);
        
        // Invalider le cache
        $this->cacheService->clearBlockCache($oldIdentifier, $oldPageSlug);
        if ($block->identifier !== $oldIdentifier || $block->page_slug !== $oldPageSlug) {
            $this->cacheService->clearBlockCache($block->identifier, $block->page_slug);
        }
        
        return redirect()->route('cms.admin.blocks.index')
            ->with('success', 'Bloc mis à jour !');
    }
    
    public function destroy(CmsBlock $block)
    {
        $identifier = $block->identifier;
        $pageSlug = $block->page_slug;
        $block->delete();
        
        // Invalider le cache
        $this->cacheService->clearBlockCache($identifier, $pageSlug);
        
        return redirect()->route('cms.admin.blocks.index')
            ->with('success', 'Bloc supprimé !');
    }
    
    /**
     * Toggle l'état actif d'un bloc
     */
    public function toggle(CmsBlock $block)
    {
        $block->update(['is_active' => !$block->is_active]);
        
        // Invalider le cache
        $this->cacheService->clearBlockCache($block->identifier, $block->page_slug);
        
        return back()->with('success', 
            $block->is_active ? 'Bloc activé !' : 'Bloc désactivé !');
    }
}


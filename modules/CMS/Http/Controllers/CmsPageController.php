<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Modules\CMS\Models\CmsPage;

class CmsPageController extends Controller
{
    public function index(Request $request)
    {
        $query = CmsPage::query();
        
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $pages = $query->orderBy('updated_at', 'desc')->paginate(15);
        
        return view('cms::pages.index', compact('pages'));
    }
    
    public function create()
    {
        $templates = [
            'default' => 'Par défaut',
            'full-width' => 'Pleine largeur',
            'sidebar' => 'Avec sidebar',
            'landing' => 'Landing page',
        ];
        
        $parentPages = CmsPage::whereNull('parent_id')
                              ->orderBy('title')
                              ->pluck('title', 'id');
        
        return view('cms::pages.create', compact('templates', 'parentPages'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'template' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'parent_id' => 'nullable|exists:cms_pages,id',
        ]);
        
        // Générer le slug si non fourni
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        // Uploader l'image si fournie
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('cms/pages', 'public');
        }
        
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        
        $page = CmsPage::create($validated);
        
        return redirect()->route('cms.pages.index')
            ->with('success', 'Page créée avec succès !');
    }
    
    public function edit(CmsPage $page)
    {
        $templates = [
            'default' => 'Par défaut',
            'full-width' => 'Pleine largeur',
            'sidebar' => 'Avec sidebar',
            'landing' => 'Landing page',
        ];
        
        $parentPages = CmsPage::whereNull('parent_id')
                              ->where('id', '!=', $page->id)
                              ->orderBy('title')
                              ->pluck('title', 'id');
        
        return view('cms::pages.edit', compact('page', 'templates', 'parentPages'));
    }
    
    public function update(Request $request, CmsPage $page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:cms_pages,slug,' . $page->id,
            'excerpt' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'template' => 'required|string',
            'featured_image' => 'nullable|image|max:2048',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'parent_id' => 'nullable|exists:cms_pages,id',
        ]);
        
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }
        
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')
                ->store('cms/pages', 'public');
        }
        
        $validated['updated_by'] = Auth::id();
        
        $page->update($validated);
        
        return redirect()->route('cms.pages.index')
            ->with('success', 'Page mise à jour !');
    }
    
    public function destroy(CmsPage $page)
    {
        $page->delete();
        
        return redirect()->route('cms.pages.index')
            ->with('success', 'Page supprimée !');
    }
}


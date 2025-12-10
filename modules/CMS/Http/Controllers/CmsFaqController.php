<?php

namespace Modules\CMS\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CMS\Models\CmsFaq;
use Modules\CMS\Models\CmsFaqCategory;
use Modules\CMS\Services\CmsCacheService;

class CmsFaqController extends Controller
{
    protected CmsCacheService $cacheService;

    public function __construct(CmsCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function index(Request $request)
    {
        $query = CmsFaq::with('category');
        
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('question', 'like', '%' . $request->search . '%')
                  ->orWhere('answer', 'like', '%' . $request->search . '%');
            });
        }
        
        $faqs = $query->orderBy('order')->paginate(20);
        $categories = CmsFaqCategory::orderBy('order')->get();
        
        return view('cms::admin.faq.index', compact('faqs', 'categories'));
    }
    
    public function create()
    {
        $categories = CmsFaqCategory::orderBy('order')->pluck('name', 'id');
        
        return view('cms::admin.faq.create', compact('categories'));
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:cms_faq_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $faq = CmsFaq::create($validated);
        
        // Invalider le cache
        $this->cacheService->clearFaqCache($faq->category_id);
        
        return redirect()->route('cms.admin.faq.index')
            ->with('success', 'FAQ créée avec succès !');
    }
    
    public function edit(CmsFaq $faq)
    {
        $categories = CmsFaqCategory::orderBy('order')->pluck('name', 'id');
        
        return view('cms::admin.faq.edit', compact('faq', 'categories'));
    }
    
    public function update(Request $request, CmsFaq $faq)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:cms_faq_categories,id',
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'order' => 'integer',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $oldCategoryId = $faq->category_id;
        $faq->update($validated);
        
        // Invalider le cache
        $this->cacheService->clearFaqCache($oldCategoryId);
        if ($faq->category_id !== $oldCategoryId) {
            $this->cacheService->clearFaqCache($faq->category_id);
        }
        
        return redirect()->route('cms.admin.faq.index')
            ->with('success', 'FAQ mise à jour !');
    }
    
    public function destroy(CmsFaq $faq)
    {
        $categoryId = $faq->category_id;
        $faq->delete();
        
        // Invalider le cache
        $this->cacheService->clearFaqCache($categoryId);
        
        return redirect()->route('cms.admin.faq.index')
            ->with('success', 'FAQ supprimée !');
    }
    
    // Gestion des catégories
    public function categories()
    {
        $categories = CmsFaqCategory::withCount('faqs')->orderBy('order')->get();
        
        return view('cms::admin.faq.categories', compact('categories'));
    }
    
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_faq_categories,slug',
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'order' => 'integer',
        ]);
        
        $category = CmsFaqCategory::create($validated);
        
        // Invalider le cache FAQ
        $this->cacheService->clearFaqCache();
        
        return back()->with('success', 'Catégorie créée !');
    }
    
    public function updateCategory(Request $request, CmsFaqCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:cms_faq_categories,slug,' . $category->id,
            'icon' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'order' => 'integer',
            'is_active' => 'boolean',
        ]);
        
        $validated['is_active'] = $request->boolean('is_active', true);
        
        $category->update($validated);
        
        // Invalider le cache FAQ
        $this->cacheService->clearFaqCache($category->id);
        
        return back()->with('success', 'Catégorie mise à jour !');
    }
    
    public function destroyCategory(CmsFaqCategory $category)
    {
        // Mettre les FAQ orphelines sans catégorie
        CmsFaq::where('category_id', $category->id)->update(['category_id' => null]);
        
        $category->delete();
        
        // Invalider le cache FAQ
        $this->cacheService->clearFaqCache();
        
        return back()->with('success', 'Catégorie supprimée !');
    }

    /**
     * Affichage public de la FAQ
     */
    public function publicIndex(Request $request)
    {
        $categoryId = $request->get('category');
        
        $categories = CmsFaqCategory::active()->withCount('activeFaqs')->orderBy('order')->get();
        
        $query = CmsFaq::active();
        
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        
        $faqs = $query->orderBy('order')->get();
        
        return view('cms::public.faq', compact('faqs', 'categories', 'categoryId'));
    }
}


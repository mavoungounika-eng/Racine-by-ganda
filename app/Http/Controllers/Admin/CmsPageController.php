/**
 * ⚠️  FICHIER OBSOLÈTE
 * Ce fichier est maintenu pour compatibilité mais sera supprimé après migration complète.
 * Utiliser modules/CMS/Http/Controllers/CmsAdminController à la place.
 */

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Services\CmsContentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Contrôleur Admin pour la gestion des pages CMS
 */
class CmsPageController extends Controller
{
    protected CmsContentService $cmsService;

    public function __construct(CmsContentService $cmsService)
    {
        $this->cmsService = $cmsService;
    }

    /**
     * Liste des pages CMS
     */
    public function index(): View
    {
        $pages = CmsPage::orderBy('created_at', 'desc')->paginate(20);

        return view('admin.cms.pages.index', compact('pages'));
    }

    /**
     * Formulaire de création
     */
    public function create(): View
    {
        return view('admin.cms.pages.create');
    }

    /**
     * Enregistrer une nouvelle page
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|unique:cms_pages,slug|max:255',
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|in:hybrid,content',
            'template' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $page = CmsPage::create($validated);

        // Invalider le cache
        $this->cmsService->clearPageCache($page->slug);

        return redirect()->route('admin.cms.pages.index')
            ->with('success', 'Page CMS créée avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(CmsPage $page): View
    {
        return view('admin.cms.pages.edit', compact('page'));
    }

    /**
     * Mettre à jour une page
     */
    public function update(Request $request, CmsPage $page): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:cms_pages,slug,' . $page->id,
            'title' => 'required|string|max:255',
            'type' => 'nullable|string|in:hybrid,content',
            'template' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $oldSlug = $page->slug;
        $page->update($validated);

        // Invalider le cache (ancien et nouveau slug)
        $this->cmsService->clearPageCache($oldSlug);
        if ($oldSlug !== $page->slug) {
            $this->cmsService->clearPageCache($page->slug);
        }

        return redirect()->route('admin.cms.pages.index')
            ->with('success', 'Page CMS mise à jour avec succès.');
    }

    /**
     * Supprimer une page
     */
    public function destroy(CmsPage $page): RedirectResponse
    {
        $slug = $page->slug;
        $page->delete();

        // Invalider le cache
        $this->cmsService->clearPageCache($slug);

        return redirect()->route('admin.cms.pages.index')
            ->with('success', 'Page CMS supprimée avec succès.');
    }
}


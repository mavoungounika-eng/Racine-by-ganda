/**
 * ⚠️  FICHIER OBSOLÈTE
 * Ce fichier est maintenu pour compatibilité mais sera supprimé après migration complète.
 * Utiliser modules/CMS/Http/Controllers/CmsAdminController à la place.
 */

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\CmsSection;
use App\Services\CmsContentService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Contrôleur Admin pour la gestion des sections CMS
 */
class CmsSectionController extends Controller
{
    protected CmsContentService $cmsService;

    public function __construct(CmsContentService $cmsService)
    {
        $this->cmsService = $cmsService;
    }

    /**
     * Liste des sections d'une page
     */
    public function index(Request $request): View
    {
        $pageSlug = $request->get('page');
        
        if ($pageSlug) {
            $page = CmsPage::where('slug', $pageSlug)->firstOrFail();
            $sections = CmsSection::where('page_slug', $pageSlug)
                ->orderBy('order')
                ->orderBy('created_at')
                ->get();
        } else {
            $page = null;
            $sections = CmsSection::orderBy('page_slug')
                ->orderBy('order')
                ->paginate(20);
        }

        $pages = CmsPage::all();

        return view('admin.cms.sections.index', compact('sections', 'page', 'pages', 'pageSlug'));
    }

    /**
     * Formulaire de création
     */
    public function create(Request $request): View
    {
        $pageSlug = $request->get('page');
        $pages = CmsPage::all();

        return view('admin.cms.sections.create', compact('pages', 'pageSlug'));
    }

    /**
     * Enregistrer une nouvelle section
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'page_slug' => 'required|string|exists:cms_pages,slug',
            'key' => 'required|string|max:255',
            'type' => 'required|string|in:text,richtext,banner,cta',
            'data' => 'nullable|json',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Convertir le JSON string en array si nécessaire
        if (isset($validated['data']) && is_string($validated['data'])) {
            $validated['data'] = json_decode($validated['data'], true);
        }

        $section = CmsSection::create($validated);

        // Invalider le cache
        $this->cmsService->clearPageCache($section->page_slug);

        return redirect()->route('admin.cms.sections.index', ['page' => $section->page_slug])
            ->with('success', 'Section CMS créée avec succès.');
    }

    /**
     * Formulaire d'édition
     */
    public function edit(CmsSection $section): View
    {
        $pages = CmsPage::all();

        return view('admin.cms.sections.edit', compact('section', 'pages'));
    }

    /**
     * Mettre à jour une section
     */
    public function update(Request $request, CmsSection $section): RedirectResponse
    {
        $validated = $request->validate([
            'page_slug' => 'required|string|exists:cms_pages,slug',
            'key' => 'required|string|max:255',
            'type' => 'required|string|in:text,richtext,banner,cta',
            'data' => 'nullable|json',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Convertir le JSON string en array si nécessaire
        if (isset($validated['data']) && is_string($validated['data'])) {
            $validated['data'] = json_decode($validated['data'], true);
        }

        $oldPageSlug = $section->page_slug;
        $section->update($validated);

        // Invalider le cache (ancien et nouveau slug si changé)
        $this->cmsService->clearPageCache($oldPageSlug);
        if ($oldPageSlug !== $section->page_slug) {
            $this->cmsService->clearPageCache($section->page_slug);
        }

        return redirect()->route('admin.cms.sections.index', ['page' => $section->page_slug])
            ->with('success', 'Section CMS mise à jour avec succès.');
    }

    /**
     * Supprimer une section
     */
    public function destroy(CmsSection $section): RedirectResponse
    {
        $pageSlug = $section->page_slug;
        $section->delete();

        // Invalider le cache
        $this->cmsService->clearPageCache($pageSlug);

        return redirect()->route('admin.cms.sections.index', ['page' => $pageSlug])
            ->with('success', 'Section CMS supprimée avec succès.');
    }
}


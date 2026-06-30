<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Cms\PageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CmsPageController extends Controller
{
    protected $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function index(Request $request)
    {
        $query = Page::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('template')) {
            $query->where('template', $request->template);
        }

        return $query->orderBy('sort_order')->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:pages,slug',
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'template' => 'required|in:default,full_width,sidebar',
            'show_in_footer' => 'boolean',
            'show_in_header' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['created_by'] = Auth::id();
        
        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $page = Page::create($validated);

        return response()->json($page, 201);
    }

    public function show($id)
    {
        return Page::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|unique:pages,slug,' . $id,
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'status' => 'sometimes|required|in:draft,published,archived',
            'template' => 'sometimes|required|in:default,full_width,sidebar',
            'show_in_footer' => 'boolean',
            'show_in_header' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['updated_by'] = Auth::id();

        if (isset($validated['status'])) {
            if ($validated['status'] === 'published' && $page->status !== 'published') {
                $validated['published_at'] = now();
            } elseif ($validated['status'] !== 'published') {
                $validated['published_at'] = null;
            }
        }

        $page->update($validated);

        return response()->json($page);
    }

    public function destroy($id)
    {
        $page = Page::findOrFail($id);
        $page->delete();

        return response()->json(null, 204);
    }

    public function publish($id)
    {
        $page = Page::findOrFail($id);
        $page->update([
            'status' => 'published',
            'published_at' => now(),
            'updated_by' => Auth::id()
        ]);

        return response()->json($page);
    }

    public function unpublish($id)
    {
        $page = Page::findOrFail($id);
        $page->update([
            'status' => 'draft',
            'published_at' => null,
            'updated_by' => Auth::id()
        ]);

        return response()->json($page);
    }
}

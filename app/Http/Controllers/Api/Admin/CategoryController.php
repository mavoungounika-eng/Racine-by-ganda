<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Cms\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        return response()->json($this->categoryService->getTree());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image_path' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
            'image_path' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'status' => 'sometimes|required|in:active,inactive',
            'sort_order' => 'integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        if ($category->children()->count() > 0) {
            return response()->json(['message' => 'Cannot delete category with children.'], 422);
        }

        $category->delete();

        return response()->json(null, 204);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:categories,id',
            'items.*.sort_order' => 'required|integer',
        ]);

        $this->categoryService->reorderCategories($request->items);

        return response()->json(['message' => 'Categories reordered successfully.']);
    }

    public function publicTree()
    {
        return response()->json($this->categoryService->getTree());
    }
}

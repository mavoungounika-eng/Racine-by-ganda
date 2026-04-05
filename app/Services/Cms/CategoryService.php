<?php

namespace App\Services\Cms;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    /**
     * Get the category tree with 1h caching.
     */
    public function getTree(): array
    {
        return Cache::tags(['cms', 'categories'])->remember('cms_category_tree', 3600, function () {
            return Category::active()->roots()
                ->with(['children' => function ($query) {
                    $query->active();
                }])
                ->get()
                ->map(function ($category) {
                    return $this->formatCategory($category);
                })
                ->toArray();
        });
    }

    /**
     * Format category for tree structure.
     */
    private function formatCategory(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
            'level' => $category->level,
            'children' => $category->children->map(function ($child) {
                return $this->formatCategory($child);
            })->toArray()
        ];
    }

    /**
     * Get flat list of categories with 1h caching.
     */
    public function getFlatList(): Collection
    {
        return Cache::tags(['cms', 'categories'])->remember('cms_category_flat', 3600, function () {
            return Category::active()
                ->orderBy('path')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Get breadcrumb for a category.
     */
    public function getBreadcrumb(Category $cat): array
    {
        if (!$cat->path) {
            return [['name' => $cat->name, 'slug' => $cat->slug, 'url' => route('frontend.shop', ['category' => $cat->slug])]];
        }

        $ids = explode('/', $cat->path);
        $categories = Category::whereIn('id', $ids)->orderBy('level')->get();

        return $categories->map(function ($category) {
            return [
                'name' => $category->name,
                'slug' => $category->slug,
                'url' => route('frontend.shop', ['category' => $category->slug])
            ];
        })->toArray();
    }

    /**
     * Reorder categories.
     */
    public function reorderCategories(array $items): void
    {
        foreach ($items as $item) {
            if (isset($item['id'], $item['sort_order'])) {
                Category::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        }
        $this->invalidateCategoryCache();
    }

    /**
     * Invalidate category cache.
     */
    public function invalidateCategoryCache(): void
    {
        Cache::forget('cms_category_tree');
        Cache::forget('cms_category_flat');
        
        try {
            Cache::tags(['cms', 'categories'])->flush();
        } catch (\BadMethodCallException $e) {
        }
    }
}

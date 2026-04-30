<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cms\CategoryService;

class CategoryObserver
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Handle the Category "saving" event.
     */
    public function saving(Category $category): void
    {
        if ($category->isDirty('parent_id') || !$category->exists) {
            if ($category->parent_id) {
                $parent = Category::find($category->parent_id);
                if ($parent) {
                    $category->level = $parent->level + 1;
                    // Note: path will be finalized in saved() because we need the ID for new categories
                }
            } else {
                $category->level = 0;
                $category->path = null;
            }
        }
    }

    /**
     * Handle the Category "saved" event.
     */
    public function saved(Category $category): void
    {
        // Finalize path for new categories or if parent changed
        if ($category->isDirty('parent_id') || !$category->path) {
            if ($category->parent_id) {
                $parent = Category::find($category->parent_id);
                $newPath = $parent->path ? $parent->path . '/' . $category->id : $parent->id . '/' . $category->id;
                
                Category::where('id', $category->id)->update(['path' => $newPath]);
            } else {
                Category::where('id', $category->id)->update(['path' => (string)$category->id]);
            }
        }

        $this->categoryService->invalidateCategoryCache();
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        $this->categoryService->invalidateCategoryCache();
    }
}

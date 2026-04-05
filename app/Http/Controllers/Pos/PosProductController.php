<?php

namespace App\Http\Controllers\Pos;

use App\Http\Resources\Pos\PosProductCollection;
use App\Http\Resources\Pos\PosProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PosProductController extends PosApiController
{
    /**
     * GET /api/pos/products
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        $query = Product::query()
            ->with(['category', 'erpDetails']);

        // Active filter (default true)
        $active = $request->query('active', 'true');
        if ($active !== null) {
            $isActive = filter_var($active, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        // In-stock filter
        if ($request->filled('in_stock')) {
            $inStock = filter_var($request->query('in_stock'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
            if ($inStock === true) {
                $query->where('stock', '>', 0);
            } elseif ($inStock === false) {
                $query->where('stock', '<=', 0);
            }
        }

        // Search filter (by name/title)
        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            if ($search !== '') {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%');
                });
            }
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'name');
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'asc';

        $sortColumn = match ($sortBy) {
            'price' => 'price',
            'created_at' => 'created_at',
            default => 'title',
        };

        $products = $query->orderBy($sortColumn, $sortDir)->paginate($perPage);

        return $this->success((new PosProductCollection($products))->toArray($request));
    }

    /**
     * GET /api/pos/products/{product}
     */
    public function show(int $product): JsonResponse
    {
        $cacheKey = "pos:product:{$product}";

        $cached = Cache::remember($cacheKey, 300, function () use ($product) {
            return Product::with(['category', 'images', 'erpDetails'])->find($product);
        });

        if (!$cached || !$cached->is_active) {
            return $this->error('PRODUCT_NOT_FOUND', 'Product not found', null, 404);
        }

        return $this->success((new PosProductResource($cached))->toArray(request()));
    }

    /**
     * GET /api/pos/products/search?q=...
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2',
        ]);

        $q = trim($validated['q']);

        $query = Product::query()
            ->with(['category', 'erpDetails'])
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($q) {
                $builder->where('title', 'like', '%' . $q . '%')
                    ->orWhereHas('erpDetails', function (Builder $sub) use ($q) {
                        $sub->where('sku', 'like', '%' . $q . '%')
                            ->orWhere('barcode', 'like', '%' . $q . '%');
                    });
            })
            ->limit(10);

        $results = $query->get();

        return $this->success([
            'results' => PosProductResource::collection($results),
        ]);
    }

    /**
     * GET /api/pos/products/categories
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('pos:categories', 600, function () {
            return Category::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'parent_id']);
        });

        return $this->success([
            'categories' => $categories,
        ]);
    }
}

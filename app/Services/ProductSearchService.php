<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    /**
     * Recherche de produits avec filtres et tri.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $filters = [])
    {
        $query = Product::with(['category', 'creator'])->where('is_active', true);

        // Recherche par mots-clés
        if (!empty($filters['q'])) {
            $searchTerm = $filters['q'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%")
                  ->orWhere('slug', 'like', "%{$searchTerm}%");
            });
        }

        // Filtre par catégorie
        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }

        // Filtre par prix min
        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }

        // Filtre par prix max
        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        // Filtre par stock disponible
        if (isset($filters['in_stock']) && $filters['in_stock']) {
            $query->where('stock', '>', 0);
        }

        // Filtre par créateur
        if (!empty($filters['creator'])) {
            $query->where('user_id', $filters['creator']);
        }

        // Tri
        $sortBy = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';

        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'popularity':
                // Tri par nombre de ventes (via order_items)
                $query->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
                      ->select('products.*', DB::raw('COALESCE(SUM(order_items.quantity), 0) as total_sold'))
                      ->groupBy('products.id')
                      ->orderBy('total_sold', 'desc');
                break;
            default:
                $query->orderBy('created_at', $sortOrder);
        }

        $perPage = $filters['per_page'] ?? 12;

        return $query->paginate($perPage)->appends($filters);
    }

    /**
     * Recherche suggérée (autocomplete).
     *
     * @param string $term
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function suggest(string $term, int $limit = 5)
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%");
            })
            ->select('id', 'title', 'slug', 'main_image', 'price')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtenir les catégories pour les filtres.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesForFilter()
    {
        return Category::whereHas('products', function ($q) {
            $q->where('is_active', true);
        })->get();
    }

    /**
     * Obtenir les créateurs pour les filtres.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCreatorsForFilter()
    {
        return \App\Models\User::whereHas('products', function ($q) {
            $q->where('is_active', true);
        })->get();
    }
}


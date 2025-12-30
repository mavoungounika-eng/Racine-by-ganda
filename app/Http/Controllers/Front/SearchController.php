<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\ProductSearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected ProductSearchService $searchService;

    public function __construct(ProductSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Afficher les résultats de recherche.
     */
    public function index(Request $request)
    {
        $filters = [
            'q' => $request->get('q'),
            'category' => $request->get('category'),
            'price_min' => $request->get('price_min'),
            'price_max' => $request->get('price_max'),
            'in_stock' => $request->boolean('in_stock'),
            'creator' => $request->get('creator'),
            'sort' => $request->get('sort', 'created_at'),
            'order' => $request->get('order', 'desc'),
            'per_page' => $request->get('per_page', 12),
        ];

        $products = $this->searchService->search($filters);
        $categories = $this->searchService->getCategoriesForFilter();
        $creators = $this->searchService->getCreatorsForFilter();

        return view('frontend.search.results', compact('products', 'categories', 'creators', 'filters'));
    }

    /**
     * API pour autocomplete (AJAX).
     */
    public function suggest(Request $request)
    {
        $term = $request->get('q', '');
        
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $suggestions = $this->searchService->suggest($term, 5);

        return response()->json($suggestions);
    }
}


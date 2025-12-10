<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Contrôleur pour la gestion des produits (admin)
 * 
 * Gère le CRUD complet des produits avec recherche, filtres et tri
 */
class AdminProductController extends AdminController
{
    /**
     * Afficher la liste des produits avec recherche et filtres.
     * 
     * @param Request $request Requête avec paramètres de recherche/filtres
     * @return View Vue avec liste paginée des produits
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);
        $query = Product::with(['category', 'erpDetails']);

        // Recherche
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Filtre par catégorie
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        // Filtre par statut
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $products = $query->paginate(15)->withQueryString();
        
        // Cache des catégories pour le filtre (rarement modifiées)
        $categories = \Illuminate\Support\Facades\Cache::remember('admin_categories_list', 3600, function () {
            return Category::orderBy('name')->get();
        });

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): View
    {
        $this->authorize('create', Product::class);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);
        $data = $request->validated();

        // Gestion de l'image
        if ($request->hasFile('main_image')) {
            $path = $request->file('main_image')->store('products', 'public');
            $data['main_image'] = basename($path);
        }

        $product = Product::create($data);
        
        // Recharger le produit pour avoir les détails ERP générés par l'observer
        $product->refresh();
        $product->load('erpDetails');

        $successMessage = 'Produit créé avec succès.';
        if ($product->sku) {
            $successMessage .= ' SKU: ' . $product->sku;
        }
        if ($product->barcode) {
            $successMessage .= ' | Code-barres: ' . $product->barcode;
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', $successMessage);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        $this->authorize('update', $product);
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $data = $request->validated();

        // Gestion de l'image
        if ($request->hasFile('main_image')) {
            // Supprimer l'ancienne image si elle existe
            if ($product->main_image) {
                Storage::disk('public')->delete('products/' . $product->main_image);
            }

            $path = $request->file('main_image')->store('products', 'public');
            $data['main_image'] = basename($path);
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        // Supprimer l'image associée
        if ($product->main_image) {
            Storage::disk('public')->delete('products/' . $product->main_image);
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produit supprimé avec succès.');
    }
}

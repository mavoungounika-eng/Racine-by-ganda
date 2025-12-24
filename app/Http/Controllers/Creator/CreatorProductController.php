<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCreatorProductRequest;
use App\Http\Requests\UpdateCreatorProductRequest;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Contrôleur pour la gestion des produits (créateur)
 * 
 * Permet aux créateurs de gérer leurs produits (CRUD)
 */
class CreatorProductController extends Controller
{
    /**
     * Afficher la liste des produits du créateur avec recherche et filtres.
     * 
     * @param Request $request Requête avec paramètres de recherche/filtres
     * @return View Vue avec liste paginée des produits et statistiques
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        $query = Product::where('user_id', $user->id);
        
        // Filtre par statut si fourni
        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }
        
        // Recherche
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        $products = $query->with('category:id,name,slug')
            ->latest()
            ->paginate(15);
        
        // Optimisation : Utiliser une seule requête avec selectRaw au lieu de 3 requêtes
        $productStats = Product::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total,
                         SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                         SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive')
            ->first();
        
        $stats = [
            'total' => $productStats->total ?? 0,
            'active' => $productStats->active ?? 0,
            'inactive' => $productStats->inactive ?? 0,
        ];
        
        return view('creator.products.index', compact('products', 'stats'));
    }

    /**
     * Afficher le formulaire de création de produit.
     */
    public function create(): View
    {
        $this->authorize('create', Product::class);

        $user = Auth::user();
        
        // PHASE 7: Vérifier si peut ajouter un produit
        $capabilityService = app(\App\Services\CreatorCapabilityService::class);
        if (!$capabilityService->canAddProduct($user)) {
            $maxProducts = $user->capability('max_products');
            $currentCount = $user->creatorProfile?->products()->count() ?? 0;
            
            return redirect()->route('creator.products.index')
                ->with('error', "Limite de produits atteinte ({$currentCount}/{$maxProducts}). Passez au plan Officiel pour des produits illimités.");
        }

        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('display_order');
            }])
            ->orderBy('display_order')
            ->get();
        
        return view('creator.products.create', compact('categories'));
    }

    /**
     * Enregistrer un nouveau produit.
     * 
     * @param StoreCreatorProductRequest $request Requête validée
     * @return RedirectResponse
     */
    public function store(StoreCreatorProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $user = Auth::user();
        
        // PHASE 7: Vérifier si peut ajouter un produit
        $capabilityService = app(\App\Services\CreatorCapabilityService::class);
        if (!$capabilityService->canAddProduct($user)) {
            $maxProducts = $user->capability('max_products');
            $currentCount = $user->creatorProfile?->products()->count() ?? 0;
            
            return redirect()->route('creator.products.index')
                ->with('error', "Limite de produits atteinte ({$currentCount}/{$maxProducts}). Passez au plan Officiel pour des produits illimités.");
        }

        $validated = $request->validated();
        
        // Génération du slug
        $slug = Str::slug($validated['title']);
        $count = Product::where('slug', 'like', "{$slug}%")->count();
        $validated['slug'] = $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
        
        // Upload de l'image principale
        if ($request->hasFile('main_image')) {
            $validated['main_image'] = $request->file('main_image')->store('products', 'public');
        }
        
        // Attribution créateur (OBLIGATOIRE pour marketplace)
        $validated['user_id'] = $user->id;
        $validated['product_type'] = 'marketplace';
        
        // Par défaut, produit en brouillon (is_active = false)
        if (!isset($validated['is_active'])) {
            $validated['is_active'] = false;
        }
        
        Product::create($validated);
        
        return redirect()->route('creator.products.index')
            ->with('success', 'Produit créé avec succès.');
    }

    /**
     * Afficher le formulaire d'édition de produit.
     */
    public function edit(Product $product): View|RedirectResponse
    {
        $this->authorize('update', $product);
        
        $categories = Category::whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($query) {
                $query->where('is_active', true)->orderBy('display_order');
            }])
            ->orderBy('display_order')
            ->get();
        
        return view('creator.products.edit', compact('product', 'categories'));
    }

    /**
     * Mettre à jour un produit.
     * 
     * @param UpdateCreatorProductRequest $request Requête validée
     * @param Product $product Produit à mettre à jour
     * @return RedirectResponse
     */
    public function update(UpdateCreatorProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validated();
        
        // Mise à jour du slug si le titre change
        if ($product->title !== $validated['title']) {
            $slug = Str::slug($validated['title']);
            $count = Product::where('slug', 'like', "{$slug}%")
                ->where('id', '!=', $product->id)
                ->count();
            $validated['slug'] = $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
        }
        
        // Upload de la nouvelle image si fournie
        if ($request->hasFile('main_image')) {
            // Supprimer l'ancienne image
            if ($product->main_image) {
                Storage::disk('public')->delete($product->main_image);
            }
            $validated['main_image'] = $request->file('main_image')->store('products', 'public');
        }
        
        // S'assurer que product_type reste 'marketplace'
        $validated['product_type'] = 'marketplace';
        
        $product->update($validated);
        
        return redirect()->route('creator.products.index')
            ->with('success', 'Produit mis à jour avec succès.');
    }

    /**
     * Supprimer un produit (soft delete ou archivage).
     */
    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);
        
        // Soft delete : désactiver le produit
        $product->update(['is_active' => false]);
        
        return redirect()->route('creator.products.index')
            ->with('success', 'Produit désactivé avec succès.');
    }

    /**
     * Publier un produit (changer is_active à true).
     */
    public function publish(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        
        $product->update(['is_active' => true]);
        
        return redirect()->route('creator.products.index')
            ->with('success', 'Produit publié avec succès.');
    }
}

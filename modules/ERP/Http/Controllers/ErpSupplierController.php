<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Http\Requests\StoreSupplierRequest;
use Modules\ERP\Http\Requests\UpdateSupplierRequest;
use Illuminate\Http\Request;

/**
 * Contrôleur de gestion des fournisseurs ERP
 * 
 * Gère le CRUD complet des fournisseurs.
 * 
 * @package Modules\ERP\Http\Controllers
 */
class ErpSupplierController extends Controller
{
    /**
     * Affiche la liste des fournisseurs
     * 
     * Permet de rechercher et filtrer les fournisseurs.
     * 
     * @param Request $request Requête avec paramètres de recherche/filtre
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = ErpSupplier::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $suppliers = $query->orderBy('name')->paginate(20);

        return view('erp::suppliers.index', compact('suppliers'));
    }

    /**
     * Affiche le formulaire de création d'un fournisseur
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('erp::suppliers.create');
    }

    /**
     * Enregistre un nouveau fournisseur
     * 
     * @param StoreSupplierRequest $request Données validées
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        ErpSupplier::create($validated);

        return redirect()->route('erp.suppliers.index')
            ->with('success', 'Fournisseur créé avec succès !');
    }

    /**
     * Affiche les détails d'un fournisseur
     * 
     * @param ErpSupplier $fournisseur Fournisseur à afficher
     * @return \Illuminate\View\View
     */
    public function show(ErpSupplier $fournisseur)
    {
        $fournisseur->load(['rawMaterials', 'purchases']);
        return view('erp::suppliers.show', compact('fournisseur'));
    }

    /**
     * Affiche le formulaire d'édition d'un fournisseur
     * 
     * @param ErpSupplier $fournisseur Fournisseur à modifier
     * @return \Illuminate\View\View
     */
    public function edit(ErpSupplier $fournisseur)
    {
        return view('erp::suppliers.edit', compact('fournisseur'));
    }

    /**
     * Met à jour un fournisseur
     * 
     * @param UpdateSupplierRequest $request Données validées
     * @param ErpSupplier $fournisseur Fournisseur à modifier
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateSupplierRequest $request, ErpSupplier $fournisseur)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $fournisseur->update($validated);

        return redirect()->route('erp.suppliers.index')
            ->with('success', 'Fournisseur mis à jour !');
    }

    /**
     * Supprime un fournisseur
     * 
     * @param ErpSupplier $fournisseur Fournisseur à supprimer
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(ErpSupplier $fournisseur)
    {
        $fournisseur->delete();

        return redirect()->route('erp.suppliers.index')
            ->with('success', 'Fournisseur supprimé !');
    }
}


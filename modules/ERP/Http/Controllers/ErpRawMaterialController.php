<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Http\Requests\StoreRawMaterialRequest;
use Modules\ERP\Http\Requests\UpdateRawMaterialRequest;
use Illuminate\Http\Request;

class ErpRawMaterialController extends Controller
{
    /**
     * Affiche la liste des matières premières
     */
    public function index(Request $request)
    {
        $query = ErpRawMaterial::with('supplier');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        $materials = $query->orderBy('name')->paginate(20);

        return view('erp::materials.index', compact('materials'));
    }

    /**
     * Formulaire de création
     */
    public function create()
    {
        $suppliers = ErpSupplier::where('is_active', true)->orderBy('name')->get();
        return view('erp::materials.create', compact('suppliers'));
    }

    /**
     * Enregistre une nouvelle matière première
     */
    public function store(StoreRawMaterialRequest $request)
    {
        ErpRawMaterial::create($request->validated());

        return redirect()->route('erp.materials.index')
            ->with('success', 'Matière première créée avec succès !');
    }

    /**
     * Affiche une matière première
     */
    public function show(ErpRawMaterial $matiere)
    {
        $matiere->load('supplier');
        return view('erp::materials.show', compact('matiere'));
    }

    /**
     * Formulaire d'édition
     */
    public function edit(ErpRawMaterial $matiere)
    {
        $suppliers = ErpSupplier::where('is_active', true)->orderBy('name')->get();
        return view('erp::materials.edit', compact('matiere', 'suppliers'));
    }

    /**
     * Met à jour une matière première
     */
    public function update(UpdateRawMaterialRequest $request, ErpRawMaterial $matiere)
    {
        $matiere->update($request->validated());

        return redirect()->route('erp.materials.index')
            ->with('success', 'Matière première mise à jour !');
    }

    /**
     * Supprime une matière première
     */
    public function destroy(ErpRawMaterial $matiere)
    {
        $matiere->delete();

        return redirect()->route('erp.materials.index')
            ->with('success', 'Matière première supprimée !');
    }
}


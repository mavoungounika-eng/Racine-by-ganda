<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Http\Requests\StoreRawMaterialRequest;
use Modules\ERP\Http\Requests\UpdateRawMaterialRequest;

class ErpRawMaterialController extends Controller
{
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
        $suppliers = ErpSupplier::orderBy('name')->get(['id', 'name']);

        return view('erp::materials.index', compact('materials', 'suppliers'));
    }

    public function dataMaterials(Request $request): JsonResponse
    {
        $query = ErpRawMaterial::with('supplier:id,name');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        if ($request->boolean('stock_critique')) {
            $query->whereColumn('current_stock', '<=', 'min_stock_alert');
        }

        $allowed = ['name', 'sku', 'current_stock', 'unit_price', 'created_at'];
        $sortBy  = in_array($request->get('sort_by'), $allowed, true) ? $request->get('sort_by') : 'name';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids   = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = ErpRawMaterial::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count.' matière(s) supprimée(s)']);
    }

    public function exportCsv(Request $request): Response
    {
        $query = ErpRawMaterial::with('supplier:id,name');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            });
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }
        if ($request->boolean('stock_critique')) {
            $query->whereColumn('current_stock', '<=', 'min_stock_alert');
        }

        $rows = $query->orderBy('name')->get();
        $csv  = "\xEF\xBB\xBF";
        $csv .= "ID;SKU;Nom;Fournisseur;Unité;Stock actuel;Stock min;Prix unitaire\n";
        foreach ($rows as $r) {
            $csv .= implode(';', [
                $r->id,
                '"'.str_replace('"', '\"\"',$r->sku ?? '').'"',
                '"'.str_replace('"', '\"\"',$r->name).'"',
                '"'.str_replace('"', '\"\"', $r->supplier ? $r->supplier->name : '').'"',
                '"'.str_replace('"', '\"\"',$r->unit ?? '').'"',
                $r->current_stock ?? 0,
                $r->min_stock_alert ?? 0,
                $r->unit_price ?? '',
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="matieres_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function create()
    {
        $suppliers = ErpSupplier::where('is_active', true)->orderBy('name')->get();
        return view('erp::materials.create', compact('suppliers'));
    }

    public function store(StoreRawMaterialRequest $request)
    {
        ErpRawMaterial::create($request->validated());

        return redirect()->route('erp.materials.index')
            ->with('success', 'Matière première créée avec succès !');
    }

    public function show(ErpRawMaterial $material)
    {
        $material->load('supplier');
        return view('erp::materials.show', compact('material'));
    }

    public function edit(ErpRawMaterial $material)
    {
        $suppliers = ErpSupplier::where('is_active', true)->orderBy('name')->get();
        return view('erp::materials.edit', compact('material', 'suppliers'));
    }

    public function update(UpdateRawMaterialRequest $request, ErpRawMaterial $material)
    {
        $material->update($request->validated());

        return redirect()->route('erp.materials.index')
            ->with('success', 'Matière première mise à jour !');
    }

    public function destroy(ErpRawMaterial $material)
    {
        $material->delete();

        return redirect()->route('erp.materials.index')
            ->with('success', 'Matière première supprimée !');
    }
}

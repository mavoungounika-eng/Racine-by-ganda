<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Http\Requests\StoreSupplierRequest;
use Modules\ERP\Http\Requests\UpdateSupplierRequest;

class ErpSupplierController extends Controller
{
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

    public function dataSuppliers(Request $request): JsonResponse
    {
        $query = ErpSupplier::withCount('rawMaterials');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active') && $request->get('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $allowed = ['name', 'email', 'is_active', 'created_at'];
        $sortBy  = in_array($request->get('sort_by'), $allowed, true) ? $request->get('sort_by') : 'name';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids   = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = ErpSupplier::whereIn('id', $ids)->delete();

        return response()->json(['success' => true, 'message' => $count.' fournisseur(s) supprimé(s)']);
    }

    public function exportCsv(Request $request): Response
    {
        $query = ErpSupplier::query();

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%");
            });
        }
        if ($request->filled('is_active') && $request->get('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rows = $query->orderBy('name')->get();
        $csv  = "\xEF\xBB\xBF";
        $csv .= "ID;Nom;Email;Téléphone;Adresse;Statut\n";
        foreach ($rows as $r) {
            $csv .= implode(';', [
                $r->id,
                '"'.str_replace('"', '\"\"', $r->name).'"',
                '"'.str_replace('"', '\"\"', $r->email ?? '').'"',
                '"'.str_replace('"', '\"\"', $r->phone ?? '').'"',
                '"'.str_replace('"', '\"\"', $r->address ?? '').'"',
                $r->is_active ? 'actif' : 'inactif',
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="fournisseurs_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function create()
    {
        return view('erp::suppliers.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        ErpSupplier::create($validated);

        return redirect()->route('erp.suppliers.index')
            ->with('success', 'Fournisseur créé avec succès !');
    }

    public function show(ErpSupplier $fournisseur)
    {
        $fournisseur->load(['rawMaterials', 'purchases']);
        return view('erp::suppliers.show', compact('fournisseur'));
    }

    public function edit(ErpSupplier $fournisseur)
    {
        return view('erp::suppliers.edit', compact('fournisseur'));
    }

    public function update(UpdateSupplierRequest $request, ErpSupplier $fournisseur)
    {
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', true);

        $fournisseur->update($validated);

        return redirect()->route('erp.suppliers.index')
            ->with('success', 'Fournisseur mis à jour !');
    }

    public function destroy(ErpSupplier $fournisseur)
    {
        $fournisseur->delete();

        return redirect()->route('erp.suppliers.index')
            ->with('success', 'Fournisseur supprimé !');
    }
}

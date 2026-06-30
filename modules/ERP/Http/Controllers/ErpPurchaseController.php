<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpPurchaseItem;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpStock;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Http\Requests\StorePurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;

class ErpPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $query = ErpPurchase::with(['supplier', 'user']);

        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->orderBy('created_at', 'desc')->paginate(20);
        $suppliers = ErpSupplier::orderBy('name')->get(['id', 'name']);

        return view('erp::purchases.index', compact('purchases', 'suppliers'));
    }

    public function dataPurchases(Request $request): JsonResponse
    {
        $query = ErpPurchase::with('supplier:id,name');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('reference', 'like', "%{$s}%");
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('purchase_date', '>=', $request->get('date_debut'));
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('purchase_date', '<=', $request->get('date_fin'));
        }

        $allowed = ['reference', 'purchase_date', 'total_amount', 'status', 'created_at'];
        $sortBy  = in_array($request->get('sort_by'), $allowed, true) ? $request->get('sort_by') : 'created_at';
        $sortDir = $request->get('sort_dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $ids   = $request->validate(['ids' => 'required|array|min:1|max:100', 'ids.*' => 'integer'])['ids'];
        $count = ErpPurchase::whereIn('id', $ids)->where('status', 'ordered')->delete();
        $skipped = count($ids) - $count;
        $msg = $count.' commande(s) supprimée(s)';
        if ($skipped > 0) {
            $msg .= ' (' . $skipped . ' ignorée(s) car déjà reçue(s)/annulée(s))';
        }

        return response()->json(['success' => true, 'message' => $msg]);
    }

    public function exportCsv(Request $request): Response
    {
        $query = ErpPurchase::with('supplier:id,name');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where('reference', 'like', "%{$s}%");
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('purchase_date', '>=', $request->get('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('purchase_date', '<=', $request->get('date_fin'));
        }

        $rows = $query->orderBy('created_at', 'desc')->get();
        $csv  = "\xEF\xBB\xBF";
        $csv .= "ID;Référence;Fournisseur;Date;Montant;Statut\n";
        foreach ($rows as $r) {
            $csv .= implode(';', [
                $r->id,
                '"'.str_replace('"', '\"\"', $r->reference ?? '').'"',
                '"'.str_replace('"', '\"\"', $r->supplier ? $r->supplier->name : '').'"',
                $r->purchase_date ? $r->purchase_date->format('Y-m-d') : '',
                $r->total_amount ?? '',
                $r->status ?? '',
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="achats_'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    public function create()
    {
        $suppliers = ErpSupplier::where('is_active', true)->orderBy('name')->get();
        $materials = ErpRawMaterial::orderBy('name')->get();

        return view('erp::purchases.create', compact('suppliers', 'materials'));
    }

    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $prefix   = config('erp.purchase.reference_prefix', 'PO');
            $length   = config('erp.purchase.reference_length', 8);
            $purchase = ErpPurchase::create([
                'reference'               => $prefix . '-' . strtoupper(Str::random($length)),
                'supplier_id'             => $validated['supplier_id'],
                'user_id'                 => Auth::id(),
                'purchase_date'           => $validated['purchase_date'],
                'expected_delivery_date'  => $validated['expected_delivery_date'] ?? null,
                'status'                  => 'ordered',
                'total_amount'            => $totalAmount,
                'notes'                   => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                ErpPurchaseItem::create([
                    'purchase_id'      => $purchase->id,
                    'purchasable_type' => ErpRawMaterial::class,
                    'purchasable_id'   => $item['material_id'],
                    'quantity'         => $item['quantity'],
                    'unit_price'       => $item['unit_price'],
                    'total_price'      => $item['quantity'] * $item['unit_price'],
                ]);
            }

            DB::commit();

            return redirect()->route('erp.purchases.index')
                ->with('success', 'Commande fournisseur créée avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur lors de la création : ' . $e->getMessage())->withInput();
        }
    }

    public function show(ErpPurchase $purchase)
    {
        $purchase->load(['supplier', 'items.purchasable', 'user', 'receptions.user']);
        return view('erp::purchases.show', compact('purchase'));
    }

    public function updateStatus(Request $request, ErpPurchase $purchase)
    {
        $request->validate([
            'status' => 'required|in:received,cancelled',
        ]);

        if ($purchase->status === 'received') {
            return back()->with('error', 'Cette commande a déjà été réceptionnée.');
        }

        try {
            DB::beginTransaction();

            $purchase->update(['status' => $request->status]);

            if ($request->status === 'received') {
                $purchase->load(['items.purchasable']);

                foreach ($purchase->items as $item) {
                    if ($item->purchasable_type === ErpRawMaterial::class) {
                        $material = $item->purchasable;

                        if ($material) {
                            $material->increment('current_stock', $item->quantity);

                            ErpStockMovement::create([
                                'stockable_type'  => ErpRawMaterial::class,
                                'stockable_id'    => $material->id,
                                'type'            => 'in',
                                'quantity'        => $item->quantity,
                                'reason'          => 'Réception commande ' . $purchase->reference,
                                'reference_type'  => ErpPurchase::class,
                                'reference_id'    => $purchase->id,
                                'user_id'         => Auth::id(),
                                'from_location'   => 'Fournisseur',
                                'to_location'     => 'Entrepôt Principal',
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Statut mis à jour avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function pdf(ErpPurchase $purchase)
    {
        $purchase->load(['supplier', 'items.purchasable', 'user']);
        $pdf = Pdf::loadView('erp::purchases.pdf', compact('purchase'))
            ->setPaper('a4', 'portrait');
        $filename = 'bon-commande-' . $purchase->reference . '.pdf';
        return $pdf->download($filename);
    }
}

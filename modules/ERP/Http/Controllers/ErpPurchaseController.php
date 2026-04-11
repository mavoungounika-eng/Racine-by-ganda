<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpPurchaseItem;
use Modules\ERP\Models\ErpSupplier;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpStock;
use Modules\ERP\Models\ErpStockMovement;
use Modules\ERP\Http\Requests\StorePurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ErpPurchaseController extends Controller
{
    /**
     * Affiche la liste des achats
     */
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

        return view('erp::purchases.index', compact('purchases'));
    }

    /**
     * Formulaire de création d'achat
     */
    public function create()
    {
        $suppliers = ErpSupplier::where('is_active', true)->orderBy('name')->get();
        $materials = ErpRawMaterial::orderBy('name')->get();
        
        return view('erp::purchases.create', compact('suppliers', 'materials'));
    }

    /**
     * Enregistre un nouvel achat
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            foreach ($validated['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $prefix = config('erp.purchase.reference_prefix', 'PO');
            $length = config('erp.purchase.reference_length', 8);
            $purchase = ErpPurchase::create([
                'reference' => $prefix . '-' . strtoupper(Str::random($length)),
                'supplier_id' => $validated['supplier_id'],
                'user_id' => Auth::id(),
                'purchase_date' => $validated['purchase_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'status' => 'ordered', // ordered, received, cancelled
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                ErpPurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'purchasable_type' => ErpRawMaterial::class,
                    'purchasable_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
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

    /**
     * Affiche le détail d'un achat
     */
    public function show(ErpPurchase $purchase)
    {
        $purchase->load(['supplier', 'items.purchasable', 'user']);
        return view('erp::purchases.show', compact('purchase'));
    }

    /**
     * Met à jour le statut (Réception)
     */
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
                // ✅ OPTIMISATION : Charger les relations en une fois pour éviter N+1
                $purchase->load(['items.purchasable']);
                
                // Incrémenter les stocks
                foreach ($purchase->items as $item) {
                    if ($item->purchasable_type === ErpRawMaterial::class) {
                        $material = $item->purchasable;
                        
                        if ($material) {
                            // Mettre à jour le stock de la matière première
                            $material->increment('current_stock', $item->quantity);
                            
                            // Créer le mouvement de stock avec la structure polymorphique correcte
                            ErpStockMovement::create([
                                'stockable_type' => ErpRawMaterial::class,
                                'stockable_id' => $material->id,
                                'type' => 'in',
                                'quantity' => $item->quantity,
                                'reason' => 'Réception commande ' . $purchase->reference,
                                'reference_type' => ErpPurchase::class,
                                'reference_id' => $purchase->id,
                                'user_id' => Auth::id(),
                                'from_location' => 'Fournisseur',
                                'to_location' => 'Entrepôt Principal',
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
}

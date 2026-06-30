<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ERP\Models\ErpPurchase;
use Modules\ERP\Models\ErpPurchaseReception;
use Modules\ERP\Models\ErpPurchaseReceptionItem;
use Modules\ERP\Models\ErpRawMaterial;
use Modules\ERP\Models\ErpStockMovement;

class ErpReceptionController extends Controller
{
    public function create(ErpPurchase $purchase)
    {
        if ($purchase->status !== 'ordered') {
            return redirect()->route('erp.purchases.show', $purchase)
                ->with('error', 'Cette commande ne peut pas être réceptionnée (statut : ' . $purchase->status . ').');
        }

        $purchase->load(['supplier', 'items.purchasable']);

        return view('erp::purchases.reception.create', compact('purchase'));
    }

    public function store(Request $request, ErpPurchase $purchase)
    {
        if ($purchase->status !== 'ordered') {
            return redirect()->route('erp.purchases.show', $purchase)
                ->with('error', 'Cette commande ne peut pas être réceptionnée.');
        }

        $purchase->load(['items.purchasable']);

        $validated = $request->validate([
            'reception_date'          => 'required|date',
            'bl_number'               => 'nullable|string|max:100',
            'notes'                   => 'nullable|string',
            'items'                   => 'required|array|min:1',
            'items.*.purchase_item_id'  => 'required|integer|exists:erp_purchase_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.quantity_refused'  => 'nullable|numeric|min:0',
            'items.*.unit_price_received' => 'required|numeric|min:0',
            'items.*.refuse_reason'     => 'nullable|string|max:255',
        ]);

        // Cross-validate: received + refused ≤ ordered
        $itemsById = $purchase->items->keyBy('id');
        foreach ($validated['items'] as $idx => $row) {
            $purchaseItem = $itemsById[$row['purchase_item_id']] ?? null;
            if (!$purchaseItem) {
                return back()->withInput()
                    ->with('error', 'Article invalide détecté.');
            }
            $received = (float) $row['quantity_received'];
            $refused  = (float) ($row['quantity_refused'] ?? 0);
            if (($received + $refused) > (float) $purchaseItem->quantity) {
                return back()->withInput()
                    ->with('error', "La somme reçue + refusée dépasse la quantité commandée pour l'article #{$idx}.");
            }
        }

        try {
            DB::beginTransaction();

            // Determine reception status
            $totalOrdered  = 0;
            $totalReceived = 0;
            foreach ($validated['items'] as $row) {
                $purchaseItem  = $itemsById[$row['purchase_item_id']];
                $totalOrdered  += (float) $purchaseItem->quantity;
                $totalReceived += (float) $row['quantity_received'];
            }

            if ($totalReceived == 0) {
                $receptionStatus = 'refused';
                $purchaseStatus  = 'cancelled';
            } elseif ($totalReceived < $totalOrdered) {
                $receptionStatus = 'partial';
                $purchaseStatus  = 'partial';
            } else {
                $receptionStatus = 'complete';
                $purchaseStatus  = 'received';
            }

            // Create reception header
            $reception = ErpPurchaseReception::create([
                'purchase_id'    => $purchase->id,
                'user_id'        => Auth::id(),
                'reception_date' => $validated['reception_date'],
                'bl_number'      => $validated['bl_number'] ?? null,
                'notes'          => $validated['notes'] ?? null,
                'status'         => $receptionStatus,
            ]);

            // Process each item
            foreach ($validated['items'] as $row) {
                $purchaseItem = $itemsById[$row['purchase_item_id']];
                $received     = (float) $row['quantity_received'];
                $refused      = (float) ($row['quantity_refused'] ?? 0);
                $unitPrice    = (float) $row['unit_price_received'];
                $refuseReason = $row['refuse_reason'] ?? null;

                ErpPurchaseReceptionItem::create([
                    'reception_id'       => $reception->id,
                    'purchase_item_id'   => $purchaseItem->id,
                    'quantity_ordered'   => $purchaseItem->quantity,
                    'quantity_received'  => $received,
                    'quantity_refused'   => $refused,
                    'unit_price_received' => $unitPrice,
                    'refuse_reason'      => $refuseReason,
                ]);

                // Update stock for raw materials only
                if ($purchaseItem->purchasable_type === ErpRawMaterial::class && $received > 0) {
                    $material = ErpRawMaterial::find($purchaseItem->purchasable_id);

                    if ($material) {
                        $material->increment('current_stock', $received);

                        // Update unit price if it changed
                        if (abs($unitPrice - (float) $material->unit_price) > 0.001) {
                            $material->update(['unit_price' => $unitPrice]);
                        }

                        ErpStockMovement::create([
                            'stockable_type' => ErpRawMaterial::class,
                            'stockable_id'   => $material->id,
                            'type'           => 'in',
                            'quantity'       => $received,
                            'reason'         => 'Réception BL ' . ($validated['bl_number'] ?? $purchase->reference),
                            'reference_type' => ErpPurchaseReception::class,
                            'reference_id'   => $reception->id,
                            'user_id'        => Auth::id(),
                            'from_location'  => 'Fournisseur',
                            'to_location'    => 'Entrepôt Principal',
                        ]);

                        // Stock alert
                        $material->refresh();
                        if ((float) $material->current_stock < (float) $material->min_stock_alert) {
                            Log::warning('ERP stock alert', [
                                'material_id'   => $material->id,
                                'material_name' => $material->name,
                                'current_stock' => $material->current_stock,
                                'min_alert'     => $material->min_stock_alert,
                            ]);
                        }

                        // Record refused quantity
                        if ($refused > 0) {
                            ErpStockMovement::create([
                                'stockable_type' => ErpRawMaterial::class,
                                'stockable_id'   => $material->id,
                                'type'           => 'refused',
                                'quantity'       => $refused,
                                'reason'         => $refuseReason ?? 'Refus réception ' . $purchase->reference,
                                'reference_type' => ErpPurchaseReception::class,
                                'reference_id'   => $reception->id,
                                'user_id'        => Auth::id(),
                                'from_location'  => 'Fournisseur',
                                'to_location'    => null,
                            ]);
                        }
                    }
                }
            }

            // Update purchase status
            $purchase->update(['status' => $purchaseStatus]);

            DB::commit();

            return redirect()->route('erp.purchases.reception.show', [$purchase, $reception])
                ->with('success', 'Réception enregistrée avec succès !');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Erreur lors de la réception : ' . $e->getMessage());
        }
    }

    public function show(ErpPurchase $purchase, ErpPurchaseReception $reception)
    {
        if ($reception->purchase_id !== $purchase->id) {
            abort(404);
        }

        $reception->load(['items.purchaseItem.purchasable', 'user', 'purchase.supplier']);

        return view('erp::purchases.reception.show', compact('purchase', 'reception'));
    }
}

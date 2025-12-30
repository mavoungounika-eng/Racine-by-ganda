<?php

namespace Modules\ERPProduction\Services;

use Modules\ERPProduction\Models\Bom;
use Modules\ERPProduction\Models\BomItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BomService
{
    /**
     * Créer une nouvelle BOM
     */
    public function createBom(int $productId, array $data): Bom
    {
        return DB::transaction(function () use ($productId, $data) {
            $bom = Bom::create([
                'product_id' => $productId,
                'version' => $data['version'] ?? '1.0',
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'quantity' => $data['quantity'] ?? 1.00,
                'unit' => $data['unit'] ?? 'unit',
                'is_active' => $data['is_active'] ?? true,
                'is_default' => $data['is_default'] ?? false,
                'created_by' => Auth::id(),
            ]);
            
            // Si BOM par défaut, désactiver les autres BOM par défaut pour ce produit
            if ($bom->is_default) {
                Bom::where('product_id', $productId)
                    ->where('id', '!=', $bom->id)
                    ->update(['is_default' => false]);
            }
            
            return $bom;
        });
    }

    /**
     * Ajouter un item à une BOM
     */
    public function addItem(Bom $bom, array $itemData): BomItem
    {
        return BomItem::create([
            'bom_id' => $bom->id,
            'raw_material_id' => $itemData['raw_material_id'],
            'quantity' => $itemData['quantity'],
            'unit' => $itemData['unit'],
            'waste_percentage' => $itemData['waste_percentage'] ?? 0.00,
            'sequence' => $itemData['sequence'] ?? 0,
            'notes' => $itemData['notes'] ?? null,
        ]);
    }

    /**
     * Mettre à jour une BOM
     */
    public function updateBom(Bom $bom, array $data): Bom
    {
        return DB::transaction(function () use ($bom, $data) {
            $bom->update([
                'name' => $data['name'] ?? $bom->name,
                'description' => $data['description'] ?? $bom->description,
                'quantity' => $data['quantity'] ?? $bom->quantity,
                'unit' => $data['unit'] ?? $bom->unit,
                'is_active' => $data['is_active'] ?? $bom->is_active,
                'is_default' => $data['is_default'] ?? $bom->is_default,
                'updated_by' => Auth::id(),
            ]);
            
            // Si BOM devient par défaut, désactiver les autres
            if ($bom->is_default && $bom->wasChanged('is_default')) {
                Bom::where('product_id', $bom->product_id)
                    ->where('id', '!=', $bom->id)
                    ->update(['is_default' => false]);
            }
            
            return $bom->fresh();
        });
    }

    /**
     * Supprimer un item de BOM
     */
    public function removeItem(BomItem $item): bool
    {
        return $item->delete();
    }

    /**
     * Dupliquer une BOM (nouvelle version)
     */
    public function duplicateBom(Bom $originalBom, string $newVersion): Bom
    {
        return DB::transaction(function () use ($originalBom, $newVersion) {
            // Créer nouvelle BOM
            $newBom = Bom::create([
                'product_id' => $originalBom->product_id,
                'version' => $newVersion,
                'name' => $originalBom->name . ' (v' . $newVersion . ')',
                'description' => $originalBom->description,
                'quantity' => $originalBom->quantity,
                'unit' => $originalBom->unit,
                'is_active' => true,
                'is_default' => false,
                'created_by' => Auth::id(),
            ]);
            
            // Copier les items
            foreach ($originalBom->items as $item) {
                BomItem::create([
                    'bom_id' => $newBom->id,
                    'raw_material_id' => $item->raw_material_id,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'waste_percentage' => $item->waste_percentage,
                    'sequence' => $item->sequence,
                    'notes' => $item->notes,
                ]);
            }
            
            return $newBom->fresh(['items']);
        });
    }

    /**
     * Obtenir BOM par défaut pour un produit
     */
    public function getDefaultBom(int $productId): ?Bom
    {
        return Bom::forProduct($productId)
            ->active()
            ->default()
            ->with('items.rawMaterial')
            ->first();
    }

    /**
     * Calculer besoins matières pour une production
     */
    public function calculateMaterialRequirements(Bom $bom, float $quantityToProduce): array
    {
        return $bom->getMaterialsForQuantity($quantityToProduce);
    }

    /**
     * Valider disponibilité stock pour production
     */
    public function validateStockAvailability(Bom $bom, float $quantityToProduce): array
    {
        $requirements = $this->calculateMaterialRequirements($bom, $quantityToProduce);
        $validation = [];
        
        foreach ($requirements as $requirement) {
            $rawMaterial = \Modules\ERP\Models\ErpRawMaterial::find($requirement['raw_material_id']);
            $available = $rawMaterial->current_stock ?? 0;
            $needed = $requirement['quantity_with_waste'];
            
            $validation[] = [
                'raw_material_id' => $requirement['raw_material_id'],
                'raw_material_name' => $requirement['raw_material_name'],
                'needed' => $needed,
                'available' => $available,
                'sufficient' => $available >= $needed,
                'shortage' => max(0, $needed - $available),
            ];
        }
        
        return $validation;
    }

    /**
     * Activer/Désactiver BOM
     */
    public function toggleActive(Bom $bom): Bom
    {
        $bom->update([
            'is_active' => !$bom->is_active,
            'updated_by' => Auth::id(),
        ]);
        
        return $bom->fresh();
    }
}

<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product;
use App\Models\User;

class Bom extends Model
{
    use SoftDeletes;

    protected $table = 'erp_boms';

    protected $fillable = [
        'product_id',
        'version',
        'name',
        'description',
        'quantity',
        'unit',
        'is_active',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Relations
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BomItem::class)->orderBy('sequence');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Calculer coût total matières (avec pertes)
     */
    public function calculateTotalMaterialCost(): float
    {
        return $this->items->sum(function ($item) {
            $rawMaterial = $item->rawMaterial;
            $quantityWithWaste = $item->quantity * (1 + $item->waste_percentage / 100);
            return $quantityWithWaste * $rawMaterial->unit_cost;
        });
    }

    /**
     * Calculer coût unitaire matières
     */
    public function calculateUnitMaterialCost(): float
    {
        if ($this->quantity <= 0) {
            return 0;
        }
        
        return $this->calculateTotalMaterialCost() / $this->quantity;
    }

    /**
     * Vérifier si BOM est complète (a au moins 1 item)
     */
    public function isComplete(): bool
    {
        return $this->items()->count() > 0;
    }

    /**
     * Obtenir liste matières nécessaires pour une quantité donnée
     */
    public function getMaterialsForQuantity(float $quantity): array
    {
        $materials = [];
        
        foreach ($this->items as $item) {
            $multiplier = $quantity / $this->quantity;
            $quantityNeeded = $item->quantity * $multiplier;
            $quantityWithWaste = $quantityNeeded * (1 + $item->waste_percentage / 100);
            
            $materials[] = [
                'raw_material_id' => $item->raw_material_id,
                'raw_material_name' => $item->rawMaterial->name,
                'quantity_needed' => $quantityNeeded,
                'waste_percentage' => $item->waste_percentage,
                'quantity_with_waste' => $quantityWithWaste,
                'unit' => $item->unit,
                'unit_cost' => $item->rawMaterial->unit_cost,
                'total_cost' => $quantityWithWaste * $item->rawMaterial->unit_cost,
            ];
        }
        
        return $materials;
    }
}

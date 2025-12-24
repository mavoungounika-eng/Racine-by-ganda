<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\ErpRawMaterial;

class BomItem extends Model
{
    protected $table = 'erp_bom_items';

    protected $fillable = [
        'bom_id',
        'raw_material_id',
        'quantity',
        'unit',
        'waste_percentage',
        'sequence',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'waste_percentage' => 'decimal:2',
        'sequence' => 'integer',
    ];

    /**
     * Relations
     */
    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(ErpRawMaterial::class, 'raw_material_id');
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Calculer quantité avec pertes
     */
    public function getQuantityWithWaste(): float
    {
        return $this->quantity * (1 + $this->waste_percentage / 100);
    }

    /**
     * Calculer coût total (quantité + pertes) × coût unitaire
     */
    public function calculateTotalCost(): float
    {
        $quantityWithWaste = $this->getQuantityWithWaste();
        return $quantityWithWaste * $this->rawMaterial->unit_cost;
    }

    /**
     * Obtenir description complète
     */
    public function getFullDescription(): string
    {
        $desc = "{$this->rawMaterial->name} - {$this->quantity} {$this->unit}";
        
        if ($this->waste_percentage > 0) {
            $desc .= " (+ {$this->waste_percentage}% perte)";
        }
        
        return $desc;
    }
}

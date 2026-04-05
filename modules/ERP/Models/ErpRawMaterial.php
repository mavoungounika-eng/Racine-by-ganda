<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ErpRawMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'reference', 'unit', 'current_stock', 
        'min_stock_alert', 'unit_price', 'supplier_id', 'description'
    ];

    protected static function newFactory()
    {
        return \Modules\ERP\Database\Factories\ErpRawMaterialFactory::new();
    }

    /**
     * Backward compatibility alias used across ERPProduction code.
     */
    public function getUnitCostAttribute(): ?float
    {
        return $this->unit_price !== null ? (float) $this->unit_price : null;
    }

    /**
     * Backward compatibility alias for writes.
     */
    public function setUnitCostAttribute($value): void
    {
        $this->attributes['unit_price'] = $value;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ErpSupplier::class);
    }

    public function stocks(): MorphMany
    {
        return $this->morphMany(ErpStock::class, 'stockable');
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(ErpStockMovement::class, 'stockable');
    }
}

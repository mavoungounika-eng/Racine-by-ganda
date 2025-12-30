<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ErpRawMaterial extends Model
{
    protected $fillable = [
        'name', 'reference', 'unit', 'current_stock', 
        'min_stock_alert', 'unit_price', 'supplier_id', 'description'
    ];

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

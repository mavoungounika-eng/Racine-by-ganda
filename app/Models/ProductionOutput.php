<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOutput extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'product_id',
        'variant_sku',
        'variant_attributes',
        'qty_good',
        'qty_second',
        'qty_rejected',
    ];

    protected $casts = [
        'variant_attributes' => 'array',
        'qty_good' => 'integer',
        'qty_second' => 'integer',
        'qty_rejected' => 'integer',
    ];

    /**
     * Relationships
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Computed Properties
     */
    public function getTotalQtyAttribute(): int
    {
        return $this->qty_good + $this->qty_second + $this->qty_rejected;
    }

    public function getQualityRateAttribute(): float
    {
        if ($this->total_qty === 0) {
            return 0;
        }

        return round(($this->qty_good / $this->total_qty) * 100, 2);
    }

    /**
     * Scopes
     */
    public function scopeForVariant($query, string $sku)
    {
        return $query->where('variant_sku', $sku);
    }

    public function scopeGoodOnly($query)
    {
        return $query->where('qty_good', '>', 0);
    }
}

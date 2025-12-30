<?php

namespace Modules\ERP\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpProductDetail extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'barcode', 'cost_price', 
        'weight', 'dimensions', 'supplier_id'
    ];

    protected $casts = [
        'dimensions' => 'array',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:3',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ErpSupplier::class);
    }
}

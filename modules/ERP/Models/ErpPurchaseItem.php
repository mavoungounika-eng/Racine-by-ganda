<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ErpPurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'purchasable_type', 'purchasable_id',
        'quantity', 'unit_price', 'total_price'
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ErpPurchase::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }
}

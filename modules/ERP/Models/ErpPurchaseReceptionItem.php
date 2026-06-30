<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpPurchaseReceptionItem extends Model
{
    protected $fillable = [
        'reception_id', 'purchase_item_id',
        'quantity_ordered', 'quantity_received', 'quantity_refused',
        'unit_price_received', 'refuse_reason',
    ];

    protected $casts = [
        'quantity_ordered'   => 'decimal:2',
        'quantity_received'  => 'decimal:2',
        'quantity_refused'   => 'decimal:2',
        'unit_price_received' => 'decimal:2',
    ];

    public function reception(): BelongsTo
    {
        return $this->belongsTo(ErpPurchaseReception::class);
    }

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(ErpPurchaseItem::class);
    }

    public function material(): ?ErpRawMaterial
    {
        $item = $this->purchaseItem;
        if (!$item) return null;
        if ($item->purchasable_type === ErpRawMaterial::class) {
            return ErpRawMaterial::find($item->purchasable_id);
        }
        return null;
    }

    public function getStatusAttribute(): string
    {
        if ($this->quantity_received == 0) return 'refused';
        if ($this->quantity_received < $this->quantity_ordered) return 'partial';
        return 'complete';
    }
}

<?php

namespace Modules\ERP\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpPurchase extends Model
{
    protected $fillable = [
        'reference', 'supplier_id', 'user_id', 'purchase_date',
        'expected_delivery_date', 'status', 'total_amount', 'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expected_delivery_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(ErpSupplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ErpPurchaseItem::class, 'purchase_id');
    }
}

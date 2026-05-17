<?php

namespace Modules\ERP\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpPurchaseReception extends Model
{
    protected $fillable = [
        'purchase_id', 'user_id', 'reception_date',
        'bl_number', 'notes', 'status',
    ];

    protected $casts = [
        'reception_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(ErpPurchase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ErpPurchaseReceptionItem::class, 'reception_id');
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    public function isRefused(): bool
    {
        return $this->status === 'refused';
    }
}

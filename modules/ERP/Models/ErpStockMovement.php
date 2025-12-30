<?php

namespace Modules\ERP\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ErpStockMovement extends Model
{
    protected $fillable = [
        'stockable_type', 'stockable_id', 'type', 'quantity',
        'from_location', 'to_location', 'reason', 
        'reference_type', 'reference_id', 'user_id'
    ];

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

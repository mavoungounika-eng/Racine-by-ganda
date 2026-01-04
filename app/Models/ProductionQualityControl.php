<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionQualityControl extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'inspector_id',
        'inspected_qty',
        'passed_qty',
        'failed_qty',
        'defect_type',
        'defect_details',
        'severity',
        'decision',
        'comments',
        'inspected_at',
    ];

    protected $casts = [
        'inspected_qty' => 'integer',
        'passed_qty' => 'integer',
        'failed_qty' => 'integer',
        'defect_details' => 'array',
        'inspected_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    /**
     * Computed Properties
     */
    public function getDefectRateAttribute(): float
    {
        if ($this->inspected_qty === 0) {
            return 0;
        }

        return round(($this->failed_qty / $this->inspected_qty) * 100, 2);
    }
}

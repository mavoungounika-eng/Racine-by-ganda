<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_type',
        'material_id',
        'material_reference',
        'quantity',
        'unit',
        'direction',
        'source_type',
        'source_id',
        'unit_cost',
        'total_value',
        'user_id',
        'notes',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeForMaterial($query, string $materialType, string $materialReference)
    {
        return $query->where('material_type', $materialType)
                     ->where('material_reference', $materialReference);
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'IN');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'OUT');
    }

    public function scopeFromProduction($query)
    {
        return $query->where('source_type', 'PRODUCTION');
    }

    public function scopeFromPurchase($query)
    {
        return $query->where('source_type', 'PURCHASE');
    }

    /**
     * Computed Properties
     */
    public function getSignedQuantityAttribute(): float
    {
        return $this->direction === 'IN' 
            ? (float) $this->quantity 
            : -(float) $this->quantity;
    }
}

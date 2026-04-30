<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionMaterialLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'material_type',
        'material_id',
        'material_reference',
        'quantity_used',
        'unit',
        'marker_efficiency',
        'waste_quantity',
        'logged_by',
        'logged_at',
        'notes',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:3',
        'marker_efficiency' => 'decimal:2',
        'waste_quantity' => 'decimal:3',
        'logged_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}

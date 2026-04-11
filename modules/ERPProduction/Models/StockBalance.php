<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\ErpRawMaterial;
use App\Models\Product;

class StockBalance extends Model
{
    protected $table = 'erp_stock_balances';

    protected $fillable = [
        'material_id',
        'product_id',
        'stock_type',
        'quantity',
        'average_cost',
        'total_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'average_cost' => 'decimal:2',
        'total_value' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(ErpRawMaterial::class, 'material_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scopes
     */
    public function scopeRaw($query)
    {
        return $query->where('stock_type', 'raw');
    }

    public function scopeWip($query)
    {
        return $query->where('stock_type', 'wip');
    }

    public function scopeFinished($query)
    {
        return $query->where('stock_type', 'finished');
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si stock est positif
     */
    public function hasStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Obtenir valeur unitaire
     */
    public function getUnitValue(): float
    {
        return $this->average_cost;
    }

    /**
     * Recalculer valeur totale
     */
    public function recalculateTotalValue(): void
    {
        $this->total_value = $this->quantity * $this->average_cost;
        $this->save();
    }
}

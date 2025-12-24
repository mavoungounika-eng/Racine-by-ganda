<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ERP\Models\ErpRawMaterial;
use App\Models\Product;
use App\Models\User;

class StockMovement extends Model
{
    protected $table = 'erp_stock_movements';

    protected $fillable = [
        'material_id',
        'product_id',
        'production_order_id',
        'type',
        'source',
        'quantity',
        'unit_cost',
        'total_cost',
        'description',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
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

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeIn($query)
    {
        return $query->where('type', 'in');
    }

    public function scopeOut($query)
    {
        return $query->where('type', 'out');
    }

    public function scopeRaw($query)
    {
        return $query->where('source', 'raw');
    }

    public function scopeWip($query)
    {
        return $query->where('source', 'wip');
    }

    public function scopeFinished($query)
    {
        return $query->where('source', 'finished');
    }

    public function scopeForMaterial($query, int $materialId)
    {
        return $query->where('material_id', $materialId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si mouvement est une entrée
     */
    public function isIn(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Vérifier si mouvement est une sortie
     */
    public function isOut(): bool
    {
        return $this->type === 'out';
    }

    /**
     * Obtenir description complète
     */
    public function getFullDescription(): string
    {
        $direction = $this->isIn() ? 'Entrée' : 'Sortie';
        $sourceLabel = match ($this->source) {
            'raw' => 'Matières premières',
            'wip' => 'En-cours',
            'finished' => 'Produits finis',
        };

        return "{$direction} {$sourceLabel} - {$this->quantity} unités @ {$this->unit_cost} FCFA";
    }
}

<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'tax_id', 'notes', 'is_active'
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\ERP\Database\Factories\ErpSupplierFactory::new();
    }

    public function rawMaterials(): HasMany
    {
        return $this->hasMany(ErpRawMaterial::class, 'supplier_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ErpPurchase::class, 'supplier_id');
    }
}

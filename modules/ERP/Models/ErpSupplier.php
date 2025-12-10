<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpSupplier extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'address', 'tax_id', 'notes', 'is_active'
    ];

    public function rawMaterials(): HasMany
    {
        return $this->hasMany(ErpRawMaterial::class, 'supplier_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ErpPurchase::class, 'supplier_id');
    }
}

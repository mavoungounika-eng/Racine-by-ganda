<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'base_plan_id',
        'included_addon_ids',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'included_addon_ids' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the base plan.
     */
    public function basePlan(): BelongsTo
    {
        return $this->belongsTo(CreatorPlan::class, 'base_plan_id');
    }

    /**
     * Get the included addons.
     */
    public function includedAddons()
    {
        if (empty($this->included_addon_ids)) {
            return collect();
        }

        return CreatorAddon::whereIn('id', $this->included_addon_ids)->get();
    }

    /**
     * Scope a query to only include active bundles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

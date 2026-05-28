<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'price',
        'quarterly_price',
        'annual_price',
        'billing_cycle',
        'is_active',
        'description',
        'features',
        'products_limit',
        'has_pos',
        'trial_days',
        'stripe_product_id',
        'stripe_price_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quarterly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'is_active' => 'boolean',
        'has_pos' => 'boolean',
        'features' => 'array',
        'products_limit' => 'integer',
        'trial_days' => 'integer',
    ];

    /**
     * Get the capabilities for this plan.
     */
    public function capabilities(): HasMany
    {
        return $this->hasMany(PlanCapability::class, 'creator_plan_id');
    }

    /**
     * Get the subscriptions for this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CreatorSubscription::class, 'creator_plan_id');
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Find a plan by its code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }
}

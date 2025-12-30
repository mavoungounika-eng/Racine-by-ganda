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
        'annual_price', // V2.1
        'billing_cycle',
        'is_active',
        'description',
        'features',
        'stripe_product_id',
        'stripe_price_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'annual_price' => 'decimal:2', // V2.1
        'is_active' => 'boolean',
        'features' => 'array',
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

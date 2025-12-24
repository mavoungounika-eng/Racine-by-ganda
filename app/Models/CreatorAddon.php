<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'capability_key',
        'capability_value',
        'billing_cycle',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'capability_value' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the subscription addons for this addon.
     */
    public function subscriptionAddons(): HasMany
    {
        return $this->hasMany(CreatorSubscriptionAddon::class);
    }

    /**
     * Scope a query to only include active addons.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

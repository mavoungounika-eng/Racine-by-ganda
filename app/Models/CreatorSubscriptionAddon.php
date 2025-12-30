<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorSubscriptionAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_subscription_id',
        'creator_addon_id',
        'activated_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the subscription.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(CreatorSubscription::class, 'creator_subscription_id');
    }

    /**
     * Get the addon.
     */
    public function addon(): BelongsTo
    {
        return $this->belongsTo(CreatorAddon::class, 'creator_addon_id');
    }

    /**
     * Check if the addon is still active (not expired).
     */
    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    /**
     * Scope a query to only include active addons.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}

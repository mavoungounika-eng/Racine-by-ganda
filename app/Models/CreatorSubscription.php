<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreatorSubscription extends Model
{
    protected $table = 'creator_subscriptions';

    protected $fillable = [
        'creator_profile_id',
        'creator_id',
        'creator_plan_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_price_id',
        'status',
        'current_period_start',
        'current_period_end',
        'started_at',
        'ends_at',
        'cancel_at_period_end',
        'canceled_at',
        'trial_start',
        'trial_end',
        'metadata',
    ];

    protected $casts = [
        'cancel_at_period_end' => 'boolean',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_start' => 'datetime',
        'trial_end' => 'datetime',
        'canceled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * Get the creator user (direct relation).
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the plan for this subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CreatorPlan::class, 'creator_plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(CreatorSubscriptionInvoice::class);
    }

    /**
     * V2.2 : Get the add-ons for this subscription.
     */
    public function addons(): HasMany
    {
        return $this->hasMany(CreatorSubscriptionAddon::class);
    }

    /**
     * V2.2 : Get the active add-ons for this subscription.
     */
    public function activeAddons(): HasMany
    {
        return $this->hasMany(CreatorSubscriptionAddon::class)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']) 
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Check if the subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->ends_at !== null && $this->ends_at->isPast();
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trialing'])
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            });
    }
}

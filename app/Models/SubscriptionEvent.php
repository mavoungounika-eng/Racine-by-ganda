<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'event',
        'from_plan_id',
        'to_plan_id',
        'amount',
        'occurred_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the creator user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the previous plan.
     */
    public function fromPlan(): BelongsTo
    {
        return $this->belongsTo(CreatorPlan::class, 'from_plan_id');
    }

    /**
     * Get the new plan.
     */
    public function toPlan(): BelongsTo
    {
        return $this->belongsTo(CreatorPlan::class, 'to_plan_id');
    }

    /**
     * Scope a query to only include events of a specific type.
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope a query to only include events in a date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }
}

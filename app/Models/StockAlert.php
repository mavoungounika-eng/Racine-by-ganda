<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAlert extends Model
{
    protected $fillable = [
        'product_id',
        'current_stock',
        'threshold',
        'status',
        'resolved_at',
        'resolved_by',
        'notes',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Get the product that this alert belongs to.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who resolved this alert.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Check if alert is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mark alert as resolved.
     */
    public function resolve(?int $userId = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Dismiss alert (manually dismissed by admin).
     */
    public function dismiss(?int $userId = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderVendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'vendor_id',
        'vendor_type',
        'subtotal',
        'commission_rate',
        'commission_amount',
        'vendor_payout',
        'status',
        'payout_status',
        'shipped_at',
        'delivered_at',
        'payout_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'vendor_payout' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'payout_at' => 'datetime',
    ];

    /**
     * Get the order that owns this vendor split
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the vendor (user) for this order split
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the order items for this vendor
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id')
            ->where('vendor_id', $this->vendor_id);
    }

    /**
     * Check if this is a brand order
     */
    public function isBrand(): bool
    {
        return $this->vendor_type === 'brand';
    }

    /**
     * Check if this is a creator order
     */
    public function isCreator(): bool
    {
        return $this->vendor_type === 'creator';
    }

    /**
     * Get vendor name
     */
    public function getVendorNameAttribute(): string
    {
        if ($this->isBrand()) {
            return 'RACINE BY GANDA';
        }

        return $this->vendor?->creatorProfile?->brand_name ?? 'Créateur';
    }

    /**
     * Scope to filter by vendor
     */
    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending payouts
     */
    public function scopePendingPayout($query)
    {
        return $query->where('payout_status', 'pending')
            ->where('status', 'delivered');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LoyaltyPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'points',
        'type',
        'source',
        'reference_id',
        'reference_type',
        'description',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'points' => 'integer',
        'expires_at' => 'datetime',
        'reference_id' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the source polymorphic relation (e.g. Order or PosSale).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeSpent($query)
    {
        return $query->where('type', 'spent');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Get balance for a customer.
     */
    public static function getBalanceFor(int $customerId): int
    {
        return (int) self::where('customer_id', $customerId)->sum('points');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyPoint extends Model
{
    protected $fillable = [
        'user_id',
        'points',
        'total_earned',
        'total_spent',
        'tier',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class, 'user_id', 'user_id');
    }

    public function calculateTier(): string
    {
        if ($this->total_earned >= 10000) {
            return 'gold';
        } elseif ($this->total_earned >= 5000) {
            return 'silver';
        }
        return 'bronze';
    }

    public function updateTier(): void
    {
        $this->update(['tier' => $this->calculateTier()]);
    }
}

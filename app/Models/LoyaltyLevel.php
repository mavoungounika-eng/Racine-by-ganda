<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'min_points',
        'color',
        'icon',
        'description',
        'benefits',
    ];

    protected $casts = [
        'min_points' => 'integer',
        'benefits' => 'array',
    ];

    /**
     * Find the level for a given points balance.
     */
    public static function forPoints(int $points): ?self
    {
        return self::where('min_points', '<=', $points)
            ->orderBy('min_points', 'desc')
            ->first();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_profile_id',
        'amount',
        'currency',
        'status',
        'reference',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}
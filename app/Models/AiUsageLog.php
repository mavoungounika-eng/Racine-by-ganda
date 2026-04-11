<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'feature',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost_usd',
        'response_cached',
        'reference_type',
        'reference_id',
        'created_at',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost_usd' => 'decimal:6',
        'response_cached' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user associated with the log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model (morph).
     */
    public function reference()
    {
        return $this->morphTo();
    }
}

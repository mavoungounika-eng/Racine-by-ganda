<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlock extends Model
{
    protected $fillable = [
        'key',
        'title',
        'type',
        'content',
        'is_active',
        'description',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the content as JSON if type is json, otherwise return as is.
     */
    public function getContentAttribute($value)
    {
        if ($this->type === 'json') {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Scope for active blocks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * User who last updated the block.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

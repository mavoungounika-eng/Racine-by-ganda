<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'color',
        'rules',
        'is_active',
        'customers_count',
        'created_by',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
        'customers_count' => 'integer',
    ];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_segment_members', 'segment_id', 'customer_id')
            ->withPivot(['assigned_by', 'assigned_at', 'expires_at', 'metadata'])
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutomatic($query)
    {
        return $query->where('type', 'automatic');
    }
}

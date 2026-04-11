<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOperation extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'name',
        'sequence_order',
        'standard_time_minutes',
        'description',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'standard_time_minutes' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(ProductionTimeLog::class);
    }

    /**
     * Computed Properties
     */
    public function getActualTimeMinutesAttribute(): int
    {
        return $this->timeLogs()->sum('duration_minutes');
    }

    public function getEfficiencyRateAttribute(): ?float
    {
        if (!$this->standard_time_minutes || $this->standard_time_minutes === 0) {
            return null;
        }

        $actualTime = $this->actual_time_minutes;
        if ($actualTime === 0) {
            return null;
        }

        return round(($this->standard_time_minutes / $actualTime) * 100, 2);
    }

    /**
     * Status Checks
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}

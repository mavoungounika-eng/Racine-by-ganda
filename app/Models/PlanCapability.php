<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanCapability extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_plan_id',
        'capability_key',
        'value',
    ];

    protected $casts = [
        'value' => 'array', // JSON par défaut, sera interprété selon le type
    ];

    /**
     * Get the plan that owns this capability.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(CreatorPlan::class, 'creator_plan_id');
    }

    /**
     * Get the capability value as a specific type.
     */
    public function getValueAsBool(): bool
    {
        $value = $this->value;
        if (is_bool($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['bool'])) {
            return (bool) $value['bool'];
        }
        return (bool) $value;
    }

    /**
     * Get the capability value as an integer.
     */
    public function getValueAsInt(): int
    {
        $value = $this->value;
        if (is_int($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['int'])) {
            return (int) $value['int'];
        }
        return (int) $value;
    }

    /**
     * Get the capability value as a string.
     */
    public function getValueAsString(): string
    {
        $value = $this->value;
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) && isset($value['string'])) {
            return (string) $value['string'];
        }
        return (string) $value;
    }

    /**
     * Get the raw value (can be any type).
     */
    public function getRawValue()
    {
        return $this->value;
    }
}

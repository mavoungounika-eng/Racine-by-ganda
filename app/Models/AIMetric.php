<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIMetric extends Model
{
    use HasFactory;

    /**
     * Override Laravel's default naming convention.
     *
     * Without this, Eloquent snake_cases the class name "AIMetric"
     * into "a_i_metrics" (the capital I gets its own segment).
     * The real table created by the migration is "ai_metrics".
     */
    protected $table = 'ai_metrics';

    protected $fillable = [
        'metric_type',
        'entity_type',
        'entity_id',
        'value',
        'metadata',
        'calculated_for_date',
    ];

    protected $casts = [
        'metadata' => 'array',
        'value' => 'decimal:2',
        'calculated_for_date' => 'date',
    ];
}

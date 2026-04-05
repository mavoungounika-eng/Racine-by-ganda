<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIMetric extends Model
{
    use HasFactory;

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

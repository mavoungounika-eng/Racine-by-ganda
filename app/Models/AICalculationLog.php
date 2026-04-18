<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AICalculationLog extends Model
{
    use HasFactory;

    /**
     * Override Laravel's default naming convention.
     *
     * Without this, Eloquent snake_cases the class name "AICalculationLog"
     * into "a_i_calculation_logs" (the capital I gets its own segment).
     * The real table created by the migration is "ai_calculation_logs".
     */
    protected $table = 'ai_calculation_logs';

    protected $fillable = [
        'module',
        'calculation_type',
        'input_data',
        'output_data',
        'calculation_time',
        'success',
        'error_message',
        'calculated_at',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'calculation_time' => 'decimal:3',
        'success' => 'boolean',
        'calculated_at' => 'datetime',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionTimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_operation_id',
        'operator_id',
        'duration_minutes',
        'started_at',
        'ended_at',
        'workstation_id',
        'notes',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function operation(): BelongsTo
    {
        return $this->belongsTo(ProductionOperation::class, 'production_operation_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}

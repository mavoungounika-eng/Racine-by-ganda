<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIAlert extends Model
{
    use HasFactory;

    /**
     * Override Laravel's default naming convention.
     *
     * Without this, Eloquent snake_cases the class name "AIAlert"
     * into "a_i_alerts" (the capital I gets its own segment).
     * The real table created by the migration is "ai_alerts".
     */
    protected $table = 'ai_alerts';

    protected $fillable = [
        'type',
        'severity',
        'category',
        'title',
        'message',
        'data',
        'triggered_by_module',
        'is_read',
        'read_by',
        'read_at',
        'triggered_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'triggered_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a lu l'alerte
     */
    public function reader(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'read_by');
    }
}

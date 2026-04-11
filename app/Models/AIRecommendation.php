<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIRecommendation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'priority',
        'category',
        'title',
        'description',
        'suggested_action',
        'data',
        'created_by_module',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur qui a révisé la recommandation
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }
}

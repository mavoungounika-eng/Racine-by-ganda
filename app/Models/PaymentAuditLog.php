<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'diff',
        'reason',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'diff' => 'array',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope : Logs pour une action spécifique
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope : Logs pour une cible spécifique
     */
    public function scopeForTarget($query, string $targetType, int $targetId)
    {
        return $query->where('target_type', $targetType)
            ->where('target_id', $targetId);
    }
}





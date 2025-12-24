<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle AdminActionDecision
 * 
 * Phase 8.2 - Tracker toutes les actions proposées et leurs décisions
 * 
 * Aucune action ne peut s'exécuter sans enregistrement ici
 */
class AdminActionDecision extends Model
{
    protected $table = 'admin_action_decisions';

    protected $fillable = [
        'action_type',
        'target_type',
        'target_id',
        'proposed_by',
        'approved_by',
        'rejected_by',
        'status',
        'confidence',
        'risk_level',
        'justification',
        'decision_reason',
        'source_data',
        'state_before',
        'state_after',
        'execution_result',
        'proposed_at',
        'approved_at',
        'rejected_at',
        'executed_at',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
        'source_data' => 'array',
        'state_before' => 'array',
        'state_after' => 'array',
        'execution_result' => 'array',
        'proposed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    /**
     * Types d'actions possibles
     */
    public const ACTION_TYPES = [
        'MONITOR' => 'Surveiller',
        'SEND_REMINDER' => 'Envoyer un rappel',
        'REQUEST_KYC_UPDATE' => 'Demander mise à jour KYC',
        'FLAG_FOR_REVIEW' => 'Marquer pour révision',
        'PROPOSE_SUSPENSION' => 'Proposer suspension',
        'NO_ACTION' => 'Aucune action',
    ];

    /**
     * Statuts possibles
     */
    public const STATUSES = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        'executed' => 'Exécuté',
        'failed' => 'Échec',
        'cancelled' => 'Annulé',
    ];

    /**
     * Relation avec l'admin qui a proposé
     */
    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    /**
     * Relation avec l'admin qui a approuvé
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relation avec l'admin qui a rejeté
     */
    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Scope : Actions en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope : Actions approuvées
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope : Actions exécutées
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }

    /**
     * Vérifier si l'action peut être exécutée
     */
    public function canBeExecuted(): bool
    {
        return $this->status === 'approved' && $this->executed_at === null;
    }

    /**
     * Marquer comme approuvé
     */
    public function approve(int $adminId, string $reason): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $adminId,
            'approved_at' => now(),
            'decision_reason' => $reason,
        ]);
    }

    /**
     * Marquer comme rejeté
     */
    public function reject(int $adminId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $adminId,
            'rejected_at' => now(),
            'decision_reason' => $reason,
        ]);
    }

    /**
     * Marquer comme exécuté
     */
    public function markAsExecuted(array $result = []): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
            'execution_result' => $result,
        ]);
    }

    /**
     * Marquer comme échec
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'executed_at' => now(),
            'execution_result' => ['error' => $error],
        ]);
    }
}




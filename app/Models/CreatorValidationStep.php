<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorValidationStep extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_profile_id',
        'step_key',
        'step_label',
        'order',
        'status',
        'assigned_to',
        'approved_by',
        'started_at',
        'completed_at',
        'notes',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Étapes de validation par défaut
     */
    public const DEFAULT_STEPS = [
        [
            'key' => 'document_review',
            'label' => 'Révision des documents',
            'order' => 1,
        ],
        [
            'key' => 'identity_verification',
            'label' => 'Vérification d\'identité',
            'order' => 2,
        ],
        [
            'key' => 'business_verification',
            'label' => 'Vérification de l\'activité',
            'order' => 3,
        ],
        [
            'key' => 'final_approval',
            'label' => 'Approbation finale',
            'order' => 4,
        ],
    ];

    /**
     * Get the creator profile that owns the validation step.
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * Get the user assigned to this step.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who approved this step.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Initialize validation steps for a creator profile.
     */
    public static function initializeForCreator(int $creatorProfileId): void
    {
        foreach (self::DEFAULT_STEPS as $step) {
            self::firstOrCreate(
                [
                    'creator_profile_id' => $creatorProfileId,
                    'step_key' => $step['key'],
                ],
                [
                    'step_label' => $step['label'],
                    'order' => $step['order'],
                    'status' => 'pending',
                ]
            );
        }
    }

    /**
     * Check if the step is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if the step is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the step is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the step is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}


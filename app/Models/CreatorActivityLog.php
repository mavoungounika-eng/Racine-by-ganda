<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorActivityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_profile_id',
        'user_id',
        'action',
        'action_label',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Actions disponibles
     */
    public const ACTIONS = [
        'verified' => 'Compte vérifié',
        'unverified' => 'Vérification retirée',
        'status_changed' => 'Statut modifié',
        'document_verified' => 'Document vérifié',
        'document_rejected' => 'Document rejeté',
        'checklist_completed' => 'Élément de checklist complété',
        'checklist_uncompleted' => 'Élément de checklist annulé',
        'note_added' => 'Note ajoutée',
        'note_updated' => 'Note modifiée',
        'note_deleted' => 'Note supprimée',
        'step_approved' => 'Étape de validation approuvée',
        'step_rejected' => 'Étape de validation rejetée',
        'assigned' => 'Assigné à un admin',
        'other' => 'Autre action',
    ];

    /**
     * Get the creator profile that owns the activity log.
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the human-readable action label.
     */
    public function getActionLabelAttribute($value): string
    {
        return $value ?? (self::ACTIONS[$this->action] ?? $this->action);
    }

    /**
     * Log an activity.
     */
    public static function log(
        int $creatorProfileId,
        int $userId,
        string $action,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): self {
        return self::create([
            'creator_profile_id' => $creatorProfileId,
            'user_id' => $userId,
            'action' => $action,
            'action_label' => self::ACTIONS[$action] ?? $action,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}


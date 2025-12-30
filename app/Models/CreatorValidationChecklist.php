<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorValidationChecklist extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_profile_id',
        'item_key',
        'item_label',
        'is_required',
        'is_completed',
        'completed_by',
        'completed_at',
        'notes',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_required' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Items de checklist par défaut
     */
    public const DEFAULT_ITEMS = [
        [
            'key' => 'profile_complete',
            'label' => 'Profil complet',
            'required' => true,
            'order' => 1,
        ],
        [
            'key' => 'identity_document',
            'label' => 'Document d\'identité (CNI/Passeport)',
            'required' => true,
            'order' => 2,
        ],
        [
            'key' => 'registration_certificate',
            'label' => 'Certificat d\'enregistrement (RCCM/NIU)',
            'required' => false,
            'order' => 3,
        ],
        [
            'key' => 'tax_id',
            'label' => 'Numéro d\'identification fiscale',
            'required' => false,
            'order' => 4,
        ],
        [
            'key' => 'bank_statement',
            'label' => 'Relevé bancaire ou RIB',
            'required' => true,
            'order' => 5,
        ],
        [
            'key' => 'portfolio',
            'label' => 'Portfolio/CV ou exemples de travaux',
            'required' => false,
            'order' => 6,
        ],
        [
            'key' => 'logo_branding',
            'label' => 'Logo et éléments de branding',
            'required' => false,
            'order' => 7,
        ],
    ];

    /**
     * Get the creator profile that owns the checklist item.
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * Get the user who completed the item.
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Calculate completion percentage for a creator profile.
     */
    public static function getCompletionPercentage(int $creatorProfileId): float
    {
        $total = self::where('creator_profile_id', $creatorProfileId)->count();
        if ($total === 0) {
            return 0;
        }

        $completed = self::where('creator_profile_id', $creatorProfileId)
            ->where('is_completed', true)
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get required items completion percentage.
     */
    public static function getRequiredCompletionPercentage(int $creatorProfileId): float
    {
        $total = self::where('creator_profile_id', $creatorProfileId)
            ->where('is_required', true)
            ->count();
        
        if ($total === 0) {
            return 0;
        }

        $completed = self::where('creator_profile_id', $creatorProfileId)
            ->where('is_required', true)
            ->where('is_completed', true)
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Initialize checklist for a creator profile.
     */
    public static function initializeForCreator(int $creatorProfileId): void
    {
        foreach (self::DEFAULT_ITEMS as $item) {
            self::firstOrCreate(
                [
                    'creator_profile_id' => $creatorProfileId,
                    'item_key' => $item['key'],
                ],
                [
                    'item_label' => $item['label'],
                    'is_required' => $item['required'],
                    'order' => $item['order'],
                ]
            );
        }
    }
}


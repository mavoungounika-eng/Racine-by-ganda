<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle pour les comptes Stripe Connect des créateurs.
 */
class CreatorStripeAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_profile_id',
        'stripe_account_id',
        'account_type',
        'onboarding_status',
        'charges_enabled',
        'payouts_enabled',
        'details_submitted',
        'requirements_currently_due',
        'requirements_eventually_due',
        'capabilities',
        'onboarding_link_url',
        'onboarding_link_expires_at',
        'last_synced_at',
    ];

    protected $casts = [
        'charges_enabled' => 'boolean',
        'payouts_enabled' => 'boolean',
        'details_submitted' => 'boolean',
        'requirements_currently_due' => 'array',
        'requirements_eventually_due' => 'array',
        'capabilities' => 'array',
        'onboarding_link_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Relation avec le profil créateur.
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }
}

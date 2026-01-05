<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class CreatorProfile extends Model
{
    use HasFactory;

    /**
     * @property-read CreatorSubscription|null $subscription Abonnement actif
     * @property-read \Illuminate\Database\Eloquent\Collection<CreatorSubscription> $subscriptions Historique abonnements
     */

    protected $fillable = [
        'user_id',
        'brand_name',
        'creator_title',
        'slug',
        'bio',
        'logo_path',
        'avatar_path',
        'banner_path',
        'photo',
        'banner',
        'location',
        'website',
        'instagram',
        'instagram_url',
        'tiktok_url',
        'facebook_url',
        'facebook',
        'type',
        'legal_status',
        'registration_number',
        'payout_method',
        'payout_details',
        'status',
        'is_verified',
        'is_active',
        'quality_score',
        'completeness_score',
        'performance_score',
        'overall_score',
        'last_score_calculated_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'payout_details' => 'array',
        'quality_score' => 'decimal:2',
        'completeness_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'last_score_calculated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($creatorProfile) {
            if (empty($creatorProfile->slug)) {
                $slug = Str::slug($creatorProfile->brand_name);
                $count = static::where('slug', 'like', "{$slug}%")->count();

                $creatorProfile->slug = $count > 0
                    ? "{$slug}-" . ($count + 1)
                    : $slug;
            }
        });

        static::updating(function ($creatorProfile) {
            if ($creatorProfile->isDirty('brand_name') && empty($creatorProfile->slug)) {
                $slug = Str::slug($creatorProfile->brand_name);
                $count = static::where('slug', 'like', "{$slug}%")
                    ->where('id', '!=', $creatorProfile->id)
                    ->count();

                $creatorProfile->slug = $count > 0
                    ? "{$slug}-" . ($count + 1)
                    : $slug;
            }
        });
    }

    /* =========================
     | RELATIONS
     ========================= */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id', 'user_id');
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'user_id', 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CreatorDocument::class);
    }

    public function validationChecklist(): HasMany
    {
        return $this->hasMany(CreatorValidationChecklist::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(CreatorActivityLog::class);
    }

    public function adminNotes(): HasMany
    {
        return $this->hasMany(CreatorAdminNote::class);
    }

    public function validationSteps(): HasMany
    {
        return $this->hasMany(CreatorValidationStep::class);
    }

    public function stripeAccount(): HasOne
    {
        return $this->hasOne(CreatorStripeAccount::class, 'creator_profile_id');
    }

    /**
     * Historique des abonnements (HasMany)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(CreatorSubscription::class, 'creator_profile_id');
    }

    /**
     * ✅ ABONNEMENT ACTIF (HasOne)
     * Contrat utilisé par :
     * - CreatorDashboardController
     * - CreatorCapabilityService
     * - Tests N+1
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(CreatorSubscription::class, 'creator_profile_id')
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>', now());
            })
            ->latest('created_at');
    }

    public function paymentPreference(): HasOne
    {
        return $this->hasOne(PaymentPreference::class, 'creator_profile_id');
    }

    /* =========================
     | SCOPES & HELPERS
     ========================= */

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActiveStatus(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo
            ? asset('storage/creators/photos/' . $this->photo)
            : null;
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner
            ? asset('storage/creators/banners/' . $this->banner)
            : null;
    }

    public function getProfileUrlAttribute(): string
    {
        return route('frontend.creator.profile', $this->slug);
    }
}

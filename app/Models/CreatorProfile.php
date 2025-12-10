<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CreatorProfile extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'brand_name',
        'slug',
        'bio',
        'logo_path',
        'banner_path',
        'photo', // Legacy
        'banner', // Legacy
        'location',
        'website',
        'instagram', // Legacy
        'instagram_url',
        'tiktok_url',
        'facebook', // Legacy
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'payout_details' => 'array', // JSON
        'quality_score' => 'decimal:2',
        'completeness_score' => 'decimal:2',
        'performance_score' => 'decimal:2',
        'overall_score' => 'decimal:2',
        'last_score_calculated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-génération du slug lors de la création
        static::creating(function ($creatorProfile) {
            if (empty($creatorProfile->slug)) {
                $slug = Str::slug($creatorProfile->brand_name);
                $count = static::where('slug', 'like', "{$slug}%")->count();
                
                $creatorProfile->slug = $count > 0 
                    ? "{$slug}-" . ($count + 1) 
                    : $slug;
            }
        });

        // Mise à jour du slug si le brand_name change
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

    /**
     * Get the user that owns the creator profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the products for the creator.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'user_id', 'user_id');
    }

    /**
     * Get the collections for the creator.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'user_id', 'user_id');
    }

    /**
     * Get the documents for the creator.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CreatorDocument::class);
    }

    /**
     * Get the validation checklist for the creator.
     */
    public function validationChecklist(): HasMany
    {
        return $this->hasMany(CreatorValidationChecklist::class);
    }

    /**
     * Get the activity logs for the creator.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(CreatorActivityLog::class);
    }

    /**
     * Get the admin notes for the creator.
     */
    public function adminNotes(): HasMany
    {
        return $this->hasMany(CreatorAdminNote::class);
    }

    /**
     * Get the validation steps for the creator.
     */
    public function validationSteps(): HasMany
    {
        return $this->hasMany(CreatorValidationStep::class);
    }

    /**
     * Scope a query to only include active creator profiles.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('status', 'active');
    }

    /**
     * Scope a query to only include pending creator profiles.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include suspended creator profiles.
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Check if the profile is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the profile is active.
     */
    public function isActiveStatus(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the profile is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Scope a query to only include verified creator profiles.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get the URL for the creator's photo.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo 
            ? asset('storage/creators/photos/' . $this->photo) 
            : null;
    }

    /**
     * Get the URL for the creator's banner.
     */
    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner 
            ? asset('storage/creators/banners/' . $this->banner) 
            : null;
    }

    /**
     * Get the full URL to the creator's public profile.
     */
    public function getProfileUrlAttribute(): string
    {
        return route('frontend.creator.profile', $this->slug);
    }
}

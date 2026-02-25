<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * CRITICAL SECURITY: Auto-increment auth_version on security changes
     * 
     * This Observer ensures all active sessions are invalidated when:
     * - role_id changes (escalation/downgrade)
     * - status changes (suspension/activation)
     * 
     * Uses 'saved' event with DB update to avoid infinite loop.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($user) {
            // SYNC LEGACY ROLE STRING -> ROLE_ID
            // If 'role' (string) is changing
            if ($user->isDirty('role')) {
                // If role_id is NOT explicitly set to a valid ID (it's either not dirty, or dirty but null)
                if (!$user->isDirty('role_id') || $user->role_id === null) {
                    $slug = $user->role;
                    $roleModel = Role::where('slug', $slug)->first();
                    if ($roleModel) {
                        $user->role_id = $roleModel->id;
                    }
                }
            }
        });

        static::saved(function ($user) {
            $changes = $user->getChanges();
            
            // CRITICAL: Check 'role' string change OR 'role_id' change OR 'password' change
            $roleChanged = array_key_exists('role_id', $changes) || array_key_exists('role', $changes);
            $statusChanged = array_key_exists('status', $changes);
            $passwordChanged = array_key_exists('password', $changes);

            if ($roleChanged || $statusChanged || $passwordChanged) {
                // Use raw DB update to avoid triggering events
                \DB::table('users')
                    ->where('id', $user->id)
                    ->increment('auth_version');
                
                // Synchroniser avec l'instance en mémoire (crucial pour les tests)
                // On récupère la vraie valeur en base car elle peut différer de l'instance (non-fillable)
                $user->auth_version = \DB::table('users')->where('id', $user->id)->value('auth_version');

                \Log::info('[AUDIT] auth_version incremented', [
                    'user_id' => $user->id,
                    'new_version' => $user->auth_version,
                    'changed_fields' => $changes,
                    'changed_by' => \Auth::id(),
                ]);
            }
        });
    }


    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'google_id', // PHASE 1.1 : Liaison OAuth Google
        'professional_email',
        'professional_email_verified',
        'professional_email_verified_at',
        'email_preferences',
        'email_notifications_enabled',
        'email_messaging_enabled',
        'password',
        'role_id',
        'role',
        'staff_role',
        'phone',
        'status',
        'is_admin',
        // 2FA
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_required',
        'trusted_device_token',
        'trusted_device_expires_at',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'trusted_device_token',
        'auth_version',
        'google_id',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'professional_email_verified' => 'boolean',
        'professional_email_verified_at' => 'datetime',
        'email_preferences' => 'array',
        'email_notifications_enabled' => 'boolean',
        'email_messaging_enabled' => 'boolean',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'role_id' => 'integer',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_required' => 'boolean',
        'trusted_device_expires_at' => 'datetime',
        'auth_version' => 'integer',
    ];

    /**
     * Get the role relation (via role_id).
     */
    public function roleRelation()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }
    
    /**
     * Alias pour roleRelation (pour compatibilité).
     */
    public function role()
    {
        return $this->roleRelation();
    }
    
    /**
     * Get the user's role string.
     */
    public function getRoleAttribute($value)
    {
        return $value ?? $this->attributes['role'] ?? null;
    }

    /**
     * Check if the user is an administrator.
     * 
     * This method is retro-compatible with the existing logic:
     * - Returns true if is_admin is true
     * - OR if role_id === 1 (legacy check)
     * - OR if the role slug is 'admin' or 'super_admin'
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        // Legacy check: is_admin flag
        if ($this->is_admin === true) {
            return true;
        }

        // Legacy check: role_id === 1 (for backward compatibility)
        if ($this->role_id === 1) {
            return true;
        }

        // New check: role slug is admin or super_admin (via relation)
        if ($this->roleRelation && in_array($this->roleRelation->slug, ['admin', 'super_admin'])) {
            return true;
        }
        
        // Check string role attribute
        if (in_array($this->attributes['role'] ?? '', ['admin', 'super_admin'])) {
            return true;
        }

        return false;
    }

    /**
     * Scope a query to only include admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true)
            ->orWhere('role_id', 1)
            ->orWhereIn('role', ['admin', 'super_admin'])
            ->orWhereHas('roleRelation', function ($q) {
                $q->whereIn('slug', ['admin', 'super_admin']);
            });
    }

    public function creatorProfile()
    {
        return $this->hasOne(CreatorProfile::class);
    }

    /**
     * Adhésions à des organisations Creator (Multi-Account)
     */
    public function memberships()
    {
        return $this->hasMany(CreatorMember::class);
    }

    /**
     * Organisations auxquelles l'utilisateur appartient
     */
    public function creatorProfiles()
    {
        return $this->belongsToMany(CreatorProfile::class, 'creator_members')
            ->withPivot('role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Get the user's appearance settings.
     */
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    /**
     * Get or create the user's appearance settings.
     */
    public function getOrCreateSettings()
    {
        return $this->settings ?? UserSetting::forUser($this->id);
    }

    /**
     * Get the user's wishlist items.
     */
    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Get the products in the user's wishlist.
     */
    public function wishlistProducts()
    {
        return $this->belongsToMany(Product::class, 'wishlists')
            ->withTimestamps();
    }

    /**
     * Get the current role slug (prioritize roleRelation, fallback to role attribute)
     */
    public function getRoleSlug(): ?string
    {
        // Priority 1: roleRelation via role_id
        if ($this->roleRelation) {
            return $this->roleRelation->slug;
        }
        
        // Priority 2: direct role attribute
        return $this->attributes['role'] ?? null;
    }

    /**
     * Check if the user is a creator.
     */
    public function isCreator(): bool
    {
        $slug = $this->getRoleSlug();
        return in_array($slug, ['createur', 'creator']);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->getRoleSlug() === $role;
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->getRoleSlug(), $roles);
    }

    /**
     * Check if the user is part of the team (super_admin, admin, staff).
     */
    public function isTeamMember(): bool
    {
        return in_array($this->getRoleSlug(), ['super_admin', 'admin', 'staff']);
    }

    /**
     * Check if the user is a client.
     */
    public function isClient(): bool
    {
        return $this->getRoleSlug() === 'client';
    }

    /**
     * Vérifier si l'utilisateur a une permission
     * 
     * @param string $permission Slug de la permission (ex: 'view-stock-analytics')
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin bypass
        if ($this->getRoleSlug() === 'super_admin') {
            return true;
        }

        // Charger relation si nécessaire
        if (!$this->relationLoaded('roleRelation')) {
            $this->load('roleRelation.permissions');
        }

        // Vérifier si le rôle a la permission
        $has = $this->roleRelation
            ?->permissions
            ?->pluck('slug')
            ?->contains($permission) ?? false;

        Log::info("[PermissionCheck] User {$this->id} ({$this->getRoleSlug()}) checking for '{$permission}': " . ($has ? 'YES' : 'NO'));
        
        return $has;
    }

    /**
     * Get the user's addresses.
     */
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the user's default address.
     */
    public function defaultAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    /**
     * Get the user's orders.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the products created by this user (for creators).
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    /**
     * Get the collections created by this user (for creators).
     */
    public function collections()
    {
        return $this->hasMany(Collection::class, 'user_id');
    }


    /**
     * Get the user's loyalty points.
     */
    public function loyaltyPoints()
    {
        return $this->hasOne(LoyaltyPoint::class);
    }

    /**
     * Get the user's loyalty transactions.
     */
    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    /**
     * Get the user's preferred email (professional if set and verified, otherwise regular email).
     */
    public function getPreferredEmailAttribute(): string
    {
        if ($this->professional_email && $this->professional_email_verified) {
            return $this->professional_email;
        }
        return $this->email;
    }

    /**
     * Check if the user has a verified professional email.
     */
    public function hasVerifiedProfessionalEmail(): bool
    {
        return $this->professional_email !== null && $this->professional_email_verified === true;
    }

    /**
     * Get email for messaging (professional if enabled, otherwise regular).
     */
    public function getMessagingEmailAttribute(): ?string
    {
        if ($this->email_messaging_enabled && $this->hasVerifiedProfessionalEmail()) {
            return $this->professional_email;
        }
        return $this->email;
    }

    /**
     * Verify the professional email.
     */
    public function verifyProfessionalEmail(): void
    {
        $this->update([
            'professional_email_verified' => true,
            'professional_email_verified_at' => now(),
        ]);
    }

    /**
     * ============================================
     * ABONNEMENT CRÉATEUR - CAPABILITIES
     * ============================================
     */

    /**
     * Get the active subscription for this creator.
     * 
     * @return \App\Models\CreatorSubscription|null
     */
    public function activeSubscription()
    {
        if (!$this->isCreator()) {
            return null;
        }

        return app(\App\Services\CreatorCapabilityService::class)
            ->getActiveSubscription($this);
    }

    /**
     * Check if the creator has a specific capability.
     * 
     * @param string $capabilityKey
     * @return bool
     */
    public function hasCapability(string $capabilityKey): bool
    {
        if (!$this->isCreator()) {
            return false;
        }

        return app(\App\Services\CreatorCapabilityService::class)
            ->can($this, $capabilityKey);
    }

    /**
     * Get the value of a specific capability.
     * 
     * @param string $capabilityKey
     * @return mixed
     */
    public function capability(string $capabilityKey)
    {
        if (!$this->isCreator()) {
            return null;
        }

        return app(\App\Services\CreatorCapabilityService::class)
            ->value($this, $capabilityKey);
    }

    /**
     * Get all capabilities for this creator.
     * 
     * @return array
     */
    public function capabilities(): array
    {
        if (!$this->isCreator()) {
            return [];
        }

        return app(\App\Services\CreatorCapabilityService::class)
            ->capabilities($this);
    }

    /**
     * Get the active plan for this creator (with fallback to FREE).
     * 
     * @return \App\Models\CreatorPlan
     */
    public function activePlan()
    {
        if (!$this->isCreator()) {
            return null;
        }

        return app(\App\Services\CreatorCapabilityService::class)
            ->getActivePlan($this);
    }

    /**
     * Get the dashboard layout for this creator.
     * 
     * @return string
     */
    public function getDashboardLayout(): string
    {
        if (!$this->isCreator()) {
            return 'basic';
        }

        return app(\App\Services\CreatorCapabilityService::class)
            ->getDashboardLayout($this);
    }

    /**
     * ============================================
     * SOCIAL AUTH V2 - OAUTH ACCOUNTS
     * ============================================
     */

    /**
     * Get the OAuth accounts for this user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function oauthAccounts()
    {
        return $this->hasMany(OauthAccount::class);
    }

    /**
     * Get the primary OAuth account for this user.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function primaryOauthAccount()
    {
        return $this->hasOne(OauthAccount::class)->where('is_primary', true);
    }

    /**
     * Get OAuth account by provider.
     * 
     * @param string $provider
     * @return OauthAccount|null
     */
    public function getOauthAccount(string $provider): ?OauthAccount
    {
        return $this->oauthAccounts()->where('provider', $provider)->first();
    }

    /**
     * Check if user has OAuth account for provider.
     * 
     * @param string $provider
     * @return bool
     */
    public function hasOAuthAccount(string $provider): bool
    {
        return $this->oauthAccounts()->where('provider', $provider)->exists();
    }
}

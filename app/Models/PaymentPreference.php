<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'creator_profile_id',
        
        // SaaS Pur - Passerelles Directes (Stripe / MoMo)
        'stripe_secret_key',
        'stripe_publishable_key',
        'momo_provider',
        'momo_api_key',
        'payment_connection_status',
        'last_connection_test_at',
        
        // Notifications
        'notify_email',
        'notify_sms',
        'notify_push',
        
        // Tax Info
        'tax_info_completed',
        'tax_id',
        'tax_country',
        
        // Metadata
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'stripe_secret_key' => 'encrypted',
        'stripe_publishable_key' => 'encrypted',
        'momo_api_key' => 'encrypted',
        'last_connection_test_at' => 'datetime',
        'notify_email' => 'boolean',
        'notify_sms' => 'boolean',
        'notify_push' => 'boolean',
        'tax_info_completed' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the creator profile that owns the payment preference.
     */
    public function creatorProfile(): BelongsTo
    {
        return $this->belongsTo(CreatorProfile::class);
    }

    /**
     * Check if the creator has a connected payment gateway.
     * CRITICAL for SaaS Pur: No connection = No active shop.
     */
    public function isConnected(): bool
    {
        return $this->payment_connection_status === 'connected';
    }

    /**
     * Get the Stripe public key if available.
     */
    public function getStripePublicKey(): ?string
    {
        return $this->stripe_publishable_key;
    }

    /**
     * Get the MoMo provider name.
     */
    public function getMoMoProviderNameAttribute(): ?string
    {
        return match($this->momo_provider) {
            'orange' => 'Orange Money',
            'mtn' => 'MTN MoMo',
            'wave' => 'Wave',
            'monetbil' => 'Monetbil',
            default => $this->momo_provider,
        };
    }
}

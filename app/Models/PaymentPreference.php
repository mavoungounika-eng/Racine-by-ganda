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
        
        // Mobile Money
        'mobile_money_operator',
        'mobile_money_number',
        'mobile_money_verified',
        'mobile_money_verified_at',
        
        // Payout Settings
        'payout_schedule',
        'minimum_payout_threshold',
        
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
        'mobile_money_verified' => 'boolean',
        'mobile_money_verified_at' => 'datetime',
        'minimum_payout_threshold' => 'integer',
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
     * Check if Mobile Money is configured.
     */
    public function hasMobileMoneyConfigured(): bool
    {
        return !empty($this->mobile_money_operator) && !empty($this->mobile_money_number);
    }

    /**
     * Check if Mobile Money is verified.
     */
    public function isMobileMoneyVerified(): bool
    {
        return $this->mobile_money_verified && $this->hasMobileMoneyConfigured();
    }

    /**
     * Get the formatted Mobile Money number.
     */
    public function getFormattedMobileMoneyNumberAttribute(): ?string
    {
        if (!$this->mobile_money_number) {
            return null;
        }

        // Format: +XXX XXX XXX XXX
        $number = preg_replace('/\D/', '', $this->mobile_money_number);
        
        if (strlen($number) === 10) {
            return sprintf(
                '+242 %s %s %s %s',
                substr($number, 0, 2),
                substr($number, 2, 3),
                substr($number, 5, 3),
                substr($number, 8, 2)
            );
        }

        return $this->mobile_money_number;
    }

    /**
     * Get the operator display name.
     */
    public function getMobileMoneyOperatorNameAttribute(): ?string
    {
        return match($this->mobile_money_operator) {
            'orange' => 'Orange Money',
            'mtn' => 'MTN MoMo',
            'wave' => 'Wave',
            default => null,
        };
    }

    /**
     * Get the payout schedule display name.
     */
    public function getPayoutScheduleNameAttribute(): string
    {
        return match($this->payout_schedule) {
            'automatic' => 'Automatique (tous les 7 jours)',
            'monthly' => 'Mensuel (le 1er du mois)',
            'manual' => 'Manuel (sur demande)',
            default => 'Non défini',
        };
    }

    /**
     * Get the formatted minimum payout threshold.
     */
    public function getFormattedMinimumPayoutThresholdAttribute(): string
    {
        return number_format($this->minimum_payout_threshold, 0, ',', ' ') . ' FCFA';
    }

    /**
     * Scope to get preferences with Mobile Money configured.
     */
    public function scopeWithMobileMoney($query)
    {
        return $query->whereNotNull('mobile_money_operator')
                    ->whereNotNull('mobile_money_number');
    }

    /**
     * Scope to get preferences with verified Mobile Money.
     */
    public function scopeWithVerifiedMobileMoney($query)
    {
        return $query->where('mobile_money_verified', true)
                    ->whereNotNull('mobile_money_operator')
                    ->whereNotNull('mobile_money_number');
    }
}

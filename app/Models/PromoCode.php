<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PromoCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_amount',
        'max_uses',
        'used_count',
        'max_uses_per_user',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    /**
     * Vérifier si le code est valide
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && Carbon::now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && Carbon::now()->gt($this->expires_at)) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur peut utiliser ce code
     */
    public function canBeUsedBy(?int $userId = null, ?string $email = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->max_uses_per_user) {
            $usageCount = $this->usages()
                ->where(function ($query) use ($userId, $email) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    } elseif ($email) {
                        $query->where('email', $email);
                    }
                })
                ->count();

            if ($usageCount >= $this->max_uses_per_user) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculer le montant de la réduction
     */
    public function calculateDiscount(float $total): float
    {
        if ($this->type === 'percentage') {
            return ($total * $this->value) / 100;
        } elseif ($this->type === 'fixed') {
            return min($this->value, $total); // Ne pas dépasser le total
        } elseif ($this->type === 'free_shipping') {
            return 0; // La livraison sera gratuite
        }

        return 0;
    }

    /**
     * Vérifier si le montant minimum est atteint
     */
    public function meetsMinimumAmount(float $total): bool
    {
        if (!$this->min_amount) {
            return true;
        }

        return $total >= $this->min_amount;
    }

    /**
     * Trouver un code promo par son code
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', strtoupper($code))->first();
    }
}


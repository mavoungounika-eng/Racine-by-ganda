<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRoutingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'currency',
        'country',
        'primary_provider_id',
        'fallback_provider_id',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Provider principal (FK bigint)
     */
    public function primaryProvider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'primary_provider_id');
    }

    /**
     * Provider de fallback (FK bigint nullable)
     */
    public function fallbackProvider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'fallback_provider_id');
    }

    /**
     * Scope : Règles actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope : Règles pour un canal spécifique
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope : Règles pour une devise spécifique
     */
    public function scopeForCurrency($query, string $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope : Règles pour un pays spécifique
     */
    public function scopeForCountry($query, string $country)
    {
        return $query->where('country', $country);
    }
}





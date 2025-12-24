<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_enabled',
        'priority',
        'currency',
        'health_status',
        'last_health_at',
        'last_event_at',
        'last_event_status',
        'meta',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'priority' => 'integer',
        'meta' => 'array',
        'last_health_at' => 'datetime',
        'last_event_at' => 'datetime',
    ];

    /**
     * Règles de routage où ce provider est primary
     */
    public function primaryRoutingRules(): HasMany
    {
        return $this->hasMany(PaymentRoutingRule::class, 'primary_provider_id');
    }

    /**
     * Règles de routage où ce provider est fallback
     */
    public function fallbackRoutingRules(): HasMany
    {
        return $this->hasMany(PaymentRoutingRule::class, 'fallback_provider_id');
    }

    /**
     * Scope : Providers activés
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope : Providers en bonne santé
     */
    public function scopeHealthy($query)
    {
        return $query->where('health_status', 'ok');
    }

    /**
     * Scope : Providers dégradés ou down
     */
    public function scopeUnhealthy($query)
    {
        return $query->whereIn('health_status', ['degraded', 'down']);
    }
}





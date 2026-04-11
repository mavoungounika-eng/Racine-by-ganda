<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modèle OauthAccount
 * 
 * Représente un compte OAuth lié à un utilisateur.
 * Supporte plusieurs providers (Google, Apple, Facebook) et permet
 * à un utilisateur d'avoir plusieurs comptes OAuth.
 * 
 * Module Social Auth v2 - Indépendant du module Google Auth v1
 */
class OauthAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'provider_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_primary',
        'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'token_expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    /**
     * Relation vers l'utilisateur
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour filtrer par provider
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope pour les comptes primaires
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Vérifier si le token est expiré
     * 
     * @return bool
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false; // Pas de token ou pas d'expiration définie
        }
        
        return $this->token_expires_at->isPast();
    }

    /**
     * Obtenir l'URL de l'avatar depuis les métadonnées
     * 
     * @return string|null
     */
    public function getAvatarUrl(): ?string
    {
        return $this->metadata['avatar'] ?? null;
    }
}

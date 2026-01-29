<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users that have this role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }


    /**
     * Permissions de ce rôle
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    // ============================================
    // ROLE CONSTANTS (Source de vérité unique)
    // ============================================
    
    /**
     * Slugs officiels des rôles
     */
    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN = 'admin';
    public const STAFF = 'staff';
    public const CREATEUR = 'createur';
    public const CLIENT = 'client';
    
    /**
     * Slugs des sous-rôles staff (tous en français)
     */
    public const STAFF_VENDEUR = 'vendeur';
    public const STAFF_CAISSIER = 'caissier';
    public const STAFF_GESTIONNAIRE_STOCK = 'gestionnaire_stock';
    public const STAFF_COMPTABLE = 'comptable';
    
    /**
     * Aliases legacy (pour rétrocompatibilité temporaire)
     */
    public const LEGACY_CREATOR = 'creator';
    
    /**
     * Obtenir tous les slugs de rôles valides
     */
    public static function getAllSlugs(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::STAFF,
            self::CREATEUR,
            self::CLIENT,
        ];
    }
    
    /**
     * Vérifier si un slug est valide
     */
    public static function isValidSlug(string $slug): bool
    {
        return in_array($slug, self::getAllSlugs(), true) 
            || $slug === self::LEGACY_CREATOR; // Temporaire
    }
    
    /**
     * Normaliser un slug (convertir legacy → officiel)
     */
    public static function normalizeSlug(string $slug): string
    {
        return match($slug) {
            self::LEGACY_CREATOR => self::CREATEUR,
            default => $slug,
        };
    }
}


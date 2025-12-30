<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle CmsPage - Pages CMS universelles
 * 
 * Gère les pages publiques du site avec leur configuration (slug, template, SEO).
 */
class CmsPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'type',
        'template',
        'seo_title',
        'seo_description',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Relation vers les sections de cette page
     */
    public function sections(): HasMany
    {
        return $this->hasMany(CmsSection::class, 'page_slug', 'slug')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Récupérer une section spécifique par sa clé
     * 
     * @param string $key
     * @return CmsSection|null
     */
    public function section(string $key): ?CmsSection
    {
        return $this->sections()
            ->where('key', $key)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Scope pour récupérer uniquement les pages publiées
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope pour récupérer une page par son slug
     */
    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}

